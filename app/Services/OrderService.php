<?php

namespace App\Services;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Exceptions\ApiException;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Support\Tenant;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected const DETAIL_RELATIONS = [
        'items.modifiers', 'table:id,table_number', 'branch:id,name',
        'placedBy:id,name', 'statusHistory',
    ];

    public function __construct(
        protected \App\Services\SubscriptionService $subscriptionService,
        protected InventoryService $inventoryService,
    ) {}

    public function list(array $filters = [])
    {
        return Order::with([
            'table:id,table_number',
            'branch:id,name',
            'placedBy:id,name',
            'items.modifiers',
        ])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['order_type'] ?? null, fn ($q, $v) => $q->where('order_type', $v))
            ->when($filters['table_id'] ?? null, fn ($q, $v) => $q->where('table_id', $v))
            ->when($filters['branch_id'] ?? null, fn ($q, $v) => $q->where('branch_id', $v))
            ->orderByDesc('created_at')
            ->get();
    }

    public function find(int $id): Order
    {
        return Order::with(self::DETAIL_RELATIONS)->findOrFail($id);
    }

    public function create(array $data, ?\App\Models\User $user): Order
    {
        return DB::transaction(function () use ($data, $user) {
            $this->subscriptionService->assertCanPlaceOrder(Tenant::id());

            $table = null;
            $branchId = $data['branch_id'] ?? null;

            if ($data['order_type'] === 'dine-in') {
                $table = DiningTable::findOrFail($data['table_id']);
                $branchId = $table->branch_id;
            }

            $this->ensureBranchSelected($data['order_type'], $branchId, $table);

            [$orderItems, $subtotalAmount] = $this->buildOrderItems($data['items']);

            $restaurant = Restaurant::allRestaurants()->find(Tenant::id());
            $taxRatePercent = $restaurant->settings['taxRatePercent'] ?? 0;
            $taxAmount = round($subtotalAmount * $taxRatePercent / 100, 2);
            $totalAmount = $subtotalAmount + $taxAmount;

            $order = Order::create([
                'branch_id' => $branchId,
                'order_number' => $this->nextOrderNumber(),
                'order_type' => $data['order_type'],
                'table_id' => $data['order_type'] === 'dine-in' ? $data['table_id'] : null,
                'customer_name' => $data['customer_name'] ?? null,
                'placed_by_user_id' => $user?->id,
                'status' => 'pending',
                'subtotal_amount' => $subtotalAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'notes' => $data['notes'] ?? '',
            ]);

            $this->persistItems($order, $orderItems);
            $this->inventoryService->deductForOrder($order);
            $order->statusHistory()->create(['status' => 'pending']);

            if ($data['order_type'] === 'dine-in') {
                $table->update(['status' => 'occupied']);
            }

            $order->load(self::DETAIL_RELATIONS);
            event(new OrderCreated($order)); // Step 7 wires this to a Reverb broadcast

            return $order;
        });
    }

    public function transitionStatus(Order $order, string $nextStatus): Order
    {
        $allowed = Order::STATUS_FLOW[$order->status] ?? [];

        if (! in_array($nextStatus, $allowed, true)) {
            $allowedText = $allowed ? implode(', ', $allowed) : 'no further changes';
            throw ApiException::badRequest(
                "This order cannot be changed from '{$order->status}' to '{$nextStatus}'. Next steps: {$allowedText}."
            );
        }

        DB::transaction(function () use ($order, $nextStatus) {
            if ($nextStatus === 'cancelled') {
                $this->inventoryService->restoreOrderStock($order);
            }
            $order->update(['status' => $nextStatus]);
            $order->statusHistory()->create(['status' => $nextStatus]);

            if ($order->order_type === 'dine-in' && $order->table_id && in_array($nextStatus, ['completed', 'cancelled'], true)) {
                $order->table()->update(['status' => 'available']);
            }
        });

        $order->refresh()->load(self::DETAIL_RELATIONS);
        event(new OrderStatusChanged($order));

        return $order;
    }

    public function cancel(Order $order): Order
    {
        return $this->transitionStatus($order, 'cancelled');
    }

    public function updateItems(Order $order, array $items): Order
    {
        if ($order->status !== 'pending') {
            throw ApiException::badRequest("Only new orders can be edited. This order is '{$order->status}'.");
        }

        return DB::transaction(function () use ($order, $items) {
            [$orderItems, $subtotalAmount] = $this->buildOrderItems($items);

            $restaurant = Restaurant::allRestaurants()->find(Tenant::id());
            $taxRatePercent = $restaurant->settings['taxRatePercent'] ?? 0;
            $taxAmount = round($subtotalAmount * $taxRatePercent / 100, 2);
            $totalAmount = max(0, $subtotalAmount + $taxAmount - $order->discount_amount);

            $this->inventoryService->restoreOrderStock($order, 'order_edit_return');
            $order->items()->delete(); // cascades to modifiers via FK
            $this->persistItems($order, $orderItems);
            $this->inventoryService->deductForOrder($order->fresh());

            $order->update([
                'subtotal_amount' => $subtotalAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            $order->load(self::DETAIL_RELATIONS);
            event(new OrderStatusChanged($order)); // reused so KDS/Orders list refreshes on an edit too

            return $order;
        });
    }

    protected function persistItems(Order $order, array $orderItems): void
    {
        foreach ($orderItems as $itemData) {
            $orderItem = $order->items()->create([
                'menu_item_id' => $itemData['menu_item_id'],
                'name' => $itemData['name'],
                'price' => $itemData['price'],
                'quantity' => $itemData['quantity'],
                'subtotal' => $itemData['subtotal'],
                'notes' => $itemData['notes'],
            ]);

            foreach ($itemData['selected_modifiers'] as $mod) {
                $orderItem->modifiers()->create($mod);
            }
        }
    }

    protected function buildOrderItems(array $requestedItems): array
    {
        $menuItemIds = collect($requestedItems)->pluck('menu_item_id')->unique();

        $menuItems = MenuItem::with('modifierGroups.options')
            ->whereIn('id', $menuItemIds)
            ->get()
            ->keyBy('id');

        if ($menuItems->count() !== $menuItemIds->count()) {
            throw ApiException::badRequest('One or more items could not be found. Refresh the menu and try again.');
        }

        $orderItems = [];
        $subtotalAmount = 0;

        foreach ($requestedItems as $requested) {
            $menuItem = $menuItems->get($requested['menu_item_id']);

            if (! $menuItem->is_available) {
                throw ApiException::badRequest("'{$menuItem->name}' is currently unavailable");
            }

            $selectedModifiers = $this->buildSelectedModifiers($menuItem, $requested['selected_modifiers'] ?? []);
            $unitPrice = $this->computeUnitPrice((float) $menuItem->price, $selectedModifiers);
            $subtotal = $unitPrice * $requested['quantity'];

            $orderItems[] = [
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => $menuItem->price,
                'quantity' => $requested['quantity'],
                'subtotal' => $subtotal,
                'notes' => $requested['notes'] ?? '',
                'selected_modifiers' => $selectedModifiers,
            ];

            $subtotalAmount += $subtotal;
        }

        return [$orderItems, $subtotalAmount];
    }

    protected function buildSelectedModifiers(MenuItem $menuItem, array $requestedModifiers): array
    {
        $requestedByGroup = collect($requestedModifiers)->groupBy('group_id');
        $selected = [];

        foreach ($menuItem->modifierGroups as $group) {
            $chosen = $requestedByGroup->get($group->id, collect());

            if ($group->is_required && $chosen->isEmpty()) {
                throw ApiException::badRequest("Choose an option for '{$group->name}' on '{$menuItem->name}'.");
            }
            if ($group->selection_type === 'single' && $chosen->count() > 1) {
                throw ApiException::badRequest("Choose only one option for '{$group->name}'.");
            }

            foreach ($chosen as $req) {
                $option = $group->options->firstWhere('id', $req['option_id']);

                if (! $option) {
                    throw ApiException::badRequest("That choice is no longer available for '{$menuItem->name}'. Refresh the menu and try again.");
                }

                $selected[] = [
                    'group_name' => $group->name,
                    'option_name' => $option->name,
                    'price_delta' => $option->price_delta,
                    'pricing_mode' => $group->pricing_mode,
                ];
            }
        }

        return $selected;
    }

    // 'override' replaces the running base price; 'additive' adds to
    // it. Identical rule to the Node version's fix for the
    // "Small = 100, not 299+100" pricing bug.
    protected function computeUnitPrice(float $basePrice, array $selectedModifiers): float
    {
        $effectiveBase = $basePrice;
        $additiveTotal = 0;

        foreach ($selectedModifiers as $mod) {
            if ($mod['pricing_mode'] === 'override') {
                $effectiveBase = (float) $mod['price_delta'];
            } else {
                $additiveTotal += (float) $mod['price_delta'];
            }
        }

        return $effectiveBase + $additiveTotal;
    }

    protected function ensureBranchSelected(string $orderType, ?int $branchId, ?DiningTable $table): void
    {
        $branchCount = Branch::count();
        if ($branchCount === 0) {
            return; // single-location restaurant — branches aren't in use, nothing to enforce
        }

        if ($orderType === 'dine-in') {
            if (! $table?->branch_id) {
                throw ApiException::badRequest('Choose a location for this table before taking orders.');
            }
            return;
        }

        if (! $branchId) {
            throw ApiException::badRequest('Choose a location before placing this order.');
        }
        if (! Branch::find($branchId)) {
            throw ApiException::badRequest('That location is not part of this restaurant.');
        }
    }

    protected function nextOrderNumber(): int
    {
        $key = 'order_number:'.Tenant::id();

        $counter = Counter::where('key', $key)->lockForUpdate()->first();

        if (! $counter) {
            $counter = Counter::create(['key' => $key, 'seq' => 1]);
            return 1;
        }

        $counter->seq += 1;
        $counter->save();

        return $counter->seq;
    }
}