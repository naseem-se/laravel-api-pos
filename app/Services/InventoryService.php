<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\MenuItem;
use App\Models\Order;
use App\Support\Tenant;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function summary(): array
    {
        $items = InventoryItem::query()->where('is_active', true)->get(['current_stock', 'low_stock_threshold', 'cost_per_unit']);

        return [
            'item_count' => $items->count(),
            'low_stock_count' => $items->filter(fn ($item) => (float) $item->current_stock <= (float) $item->low_stock_threshold)->count(),
            'stock_value' => round($items->sum(fn ($item) => (float) $item->current_stock * (float) $item->cost_per_unit), 2),
        ];
    }

    public function list(array $filters = [])
    {
        return InventoryItem::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('sku', 'like', '%'.$search.'%');
            }))
            ->when(($filters['low_stock'] ?? false), fn ($query) => $query->whereColumn('current_stock', '<=', 'low_stock_threshold'))
            ->when(isset($filters['is_active']), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->withCount('menuItems')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data, ?int $userId): InventoryItem
    {
        return DB::transaction(function () use ($data, $userId) {
            $this->assertSkuAvailable($data['sku'] ?? null);
            $startingStock = (float) ($data['current_stock'] ?? 0);
            unset($data['current_stock']);

            $item = InventoryItem::create([
                ...$data,
                'current_stock' => 0,
                'is_active' => true,
            ]);

            if ($startingStock > 0) {
                $this->applyMovement(
                    $item,
                    $startingStock,
                    'opening',
                    (float) ($data['cost_per_unit'] ?? 0),
                    'opening_stock',
                    $item->id,
                    'Starting stock',
                    $userId,
                );
            }

            return $item->fresh()->loadCount('menuItems');
        });
    }

    public function update(InventoryItem $item, array $data): InventoryItem
    {
        $this->assertSkuAvailable($data['sku'] ?? null, $item->id);
        $item->update($data);

        return $item->fresh()->loadCount('menuItems');
    }

    public function delete(InventoryItem $item): void
    {
        if ((float) $item->current_stock > 0) {
            throw ApiException::conflict('This item still has stock. Use an adjustment to clear it before archiving.');
        }

        if ($item->menuItems()->exists()) {
            throw ApiException::conflict('This item is used in a menu recipe. Remove it from the recipe before archiving.');
        }

        $item->delete();
    }

    public function adjust(InventoryItem $item, array $data, ?int $userId): InventoryItem
    {
        return DB::transaction(function () use ($item, $data, $userId) {
            $quantity = (float) $data['quantity'] * ($data['direction'] === 'remove' ? -1 : 1);
            $type = $data['direction'] === 'remove' ? 'adjustment_out' : 'adjustment_in';
            $unitCost = (float) ($data['unit_cost'] ?? $item->cost_per_unit);
            $branchId = $data['branch_id'] ?? null;
            if ($branchId) {
                Branch::findOrFail($branchId);
            }

            $this->applyMovement(
                $item,
                $quantity,
                $type,
                $unitCost,
                'manual_adjustment',
                null,
                $data['notes'],
                $userId,
                $branchId,
            );

            return $item->fresh()->loadCount('menuItems');
        });
    }

    public function transactions(InventoryItem $item)
    {
        return $item->transactions()
            ->with('createdBy:id,name')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();
    }

    public function recipe(MenuItem $menuItem): array
    {
        return $menuItem->inventoryItems()
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => [
                'inventory_item_id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'quantity_per_item' => (float) $item->pivot->quantity_per_item,
            ])
            ->all();
    }

    public function updateRecipe(MenuItem $menuItem, array $lines): array
    {
        $itemIds = collect($lines)->pluck('inventory_item_id');
        $foundCount = InventoryItem::whereIn('id', $itemIds)->where('is_active', true)->count();

        if ($foundCount !== $itemIds->unique()->count()) {
            throw ApiException::badRequest('One or more stock items could not be found or are inactive.');
        }

        $sync = collect($lines)->mapWithKeys(fn ($line) => [
            $line['inventory_item_id'] => [
                'restaurant_id' => Tenant::id(),
                'quantity_per_item' => $line['quantity_per_item'],
            ],
        ])->all();

        $menuItem->inventoryItems()->sync($sync);

        return $this->recipe($menuItem);
    }

    public function deductForOrder(Order $order): void
    {
        $order->loadMissing('items.menuItem.inventoryItems');

        foreach ($order->items as $orderItem) {
            $menuItem = $orderItem->menuItem;
            if (! $menuItem) {
                continue;
            }

            foreach ($menuItem->inventoryItems as $recipeLine) {
                $inventoryItem = InventoryItem::query()->lockForUpdate()->findOrFail($recipeLine->id);
                $quantity = (float) $recipeLine->pivot->quantity_per_item * $orderItem->quantity;

                if ((float) $inventoryItem->current_stock < $quantity) {
                    throw ApiException::badRequest("Not enough '{$inventoryItem->name}' in stock for this order.");
                }

                $this->applyMovement(
                    $inventoryItem,
                    -$quantity,
                    'order_use',
                    (float) $inventoryItem->cost_per_unit,
                    'order_item',
                    $orderItem->id,
                    "Used for order #{$order->order_number}",
                    $order->placed_by_user_id,
                    $order->branch_id,
                );
            }
        }
    }

    public function restoreOrderStock(Order $order, string $movementType = 'order_return'): void
    {
        $orderItemIds = $order->items()->pluck('id');
        if ($orderItemIds->isEmpty()) {
            return;
        }

        $uses = InventoryTransaction::query()
            ->where('type', 'order_use')
            ->where('reference_type', 'order_item')
            ->whereIn('reference_id', $orderItemIds)
            ->get();

        foreach ($uses as $use) {
            $item = InventoryItem::withTrashed()->find($use->inventory_item_id);
            if (! $item) {
                continue;
            }

            $this->applyMovement(
                $item,
                abs((float) $use->quantity_change),
                $movementType,
                (float) $use->unit_cost,
                $movementType,
                $order->id,
                ucfirst(str_replace('_', ' ', $movementType))." for order #{$order->order_number}",
                $order->placed_by_user_id,
                $order->branch_id,
            );
        }
    }

    public function addPurchaseStock(int $inventoryItemId, float $quantity, float $unitCost, int $purchaseId, string $purchaseNumber, ?int $userId, ?int $branchId): void
    {
        $item = InventoryItem::query()->lockForUpdate()->findOrFail($inventoryItemId);
        $this->applyMovement(
            $item,
            $quantity,
            'purchase',
            $unitCost,
            'purchase',
            $purchaseId,
            "Received purchase {$purchaseNumber}",
            $userId,
            $branchId,
        );
    }

    protected function applyMovement(
        InventoryItem $item,
        float $quantityChange,
        string $type,
        float $unitCost,
        ?string $referenceType,
        ?int $referenceId,
        ?string $notes,
        ?int $userId,
        ?int $branchId = null,
    ): InventoryTransaction {
        $itemQuery = $item->trashed() ? InventoryItem::withTrashed() : InventoryItem::query();
        $item = $itemQuery->lockForUpdate()->findOrFail($item->id);
        $oldStock = (float) $item->current_stock;
        $newStock = round($oldStock + $quantityChange, 4);

        if ($newStock < 0) {
            throw ApiException::badRequest("Not enough '{$item->name}' in stock.");
        }

        $oldCost = (float) $item->cost_per_unit;
        if ($quantityChange > 0 && $newStock > 0) {
            $unitCost = max(0, $unitCost);
            $item->cost_per_unit = round((($oldStock * $oldCost) + ($quantityChange * $unitCost)) / $newStock, 4);
        }

        $item->current_stock = $newStock;
        $item->save();

        return InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'branch_id' => $branchId,
            'type' => $type,
            'quantity_change' => $quantityChange,
            'unit_cost' => $unitCost,
            'balance_after' => $newStock,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by_user_id' => $userId,
        ]);
    }

    protected function assertSkuAvailable(?string $sku, ?int $ignoreId = null): void
    {
        if (! $sku) {
            return;
        }

        $exists = InventoryItem::withTrashed()
            ->where('sku', $sku)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ApiException::badRequest('This item code is already in use.');
        }
    }
}
