<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Support\Facades\DB;

class PurchasingService
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function suppliers()
    {
        return Supplier::withCount('purchases')->orderBy('name')->get();
    }

    public function createSupplier(array $data): Supplier
    {
        return Supplier::create([...$data, 'is_active' => true]);
    }

    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->fresh()->loadCount('purchases');
    }

    public function purchases(array $filters = [])
    {
        return Purchase::query()
            ->with(['supplier:id,name', 'branch:id,name', 'items.inventoryItem:id,name,unit', 'payments'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['supplier_id'] ?? null, fn ($query, $supplierId) => $query->where('supplier_id', $supplierId))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('purchase_date', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('purchase_date', '<=', $date))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Purchase $purchase) => $this->presentPurchase($purchase));
    }

    public function findPurchase(Purchase $purchase): array
    {
        return $this->presentPurchase($purchase->load(['supplier:id,name', 'branch:id,name', 'items.inventoryItem:id,name,unit', 'payments.createdBy:id,name']));
    }

    public function createPurchase(array $data, User $user): array
    {
        return DB::transaction(function () use ($data, $user) {
            $supplier = $this->findSupplier($data['supplier_id'] ?? null);
            $branch = $this->findBranch($data['branch_id'] ?? null);
            [$lines, $subtotal] = $this->prepareLines($data['items']);
            $tax = (float) ($data['tax_amount'] ?? 0);
            $discount = (float) ($data['discount_amount'] ?? 0);
            $total = round($subtotal + $tax - $discount, 2);

            if ($discount > $subtotal + $tax) {
                throw ApiException::badRequest('The discount cannot be greater than the purchase total.');
            }

            $purchase = Purchase::create([
                'branch_id' => $branch?->id,
                'supplier_id' => $supplier?->id,
                'created_by_user_id' => $user->id,
                'status' => 'draft',
                'purchase_date' => $data['purchase_date'],
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'subtotal_amount' => $subtotal,
                'tax_amount' => $tax,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'notes' => $data['notes'] ?? null,
            ]);
            $purchase->update(['purchase_number' => 'PO-'.str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT)]);
            $purchase->items()->createMany($lines);

            return $this->findPurchase($purchase->fresh());
        });
    }

    public function updateDraft(Purchase $purchase, array $data): array
    {
        if ($purchase->status !== 'draft') {
            throw ApiException::badRequest('Only draft purchases can be changed.');
        }

        return DB::transaction(function () use ($purchase, $data) {
            if (array_key_exists('supplier_id', $data)) {
                $data['supplier_id'] = $this->findSupplier($data['supplier_id'])?->id;
            }
            if (array_key_exists('branch_id', $data)) {
                $data['branch_id'] = $this->findBranch($data['branch_id'])?->id;
            }

            if (array_key_exists('items', $data)) {
                [$lines, $subtotal] = $this->prepareLines($data['items']);
                $tax = (float) ($data['tax_amount'] ?? $purchase->tax_amount);
                $discount = (float) ($data['discount_amount'] ?? $purchase->discount_amount);
                if ($discount > $subtotal + $tax) {
                    throw ApiException::badRequest('The discount cannot be greater than the purchase total.');
                }
                $data['subtotal_amount'] = $subtotal;
                $data['total_amount'] = round($subtotal + $tax - $discount, 2);
                $purchase->items()->delete();
                $purchase->items()->createMany($lines);
            } elseif (array_key_exists('tax_amount', $data) || array_key_exists('discount_amount', $data)) {
                $tax = (float) ($data['tax_amount'] ?? $purchase->tax_amount);
                $discount = (float) ($data['discount_amount'] ?? $purchase->discount_amount);
                if ($discount > (float) $purchase->subtotal_amount + $tax) {
                    throw ApiException::badRequest('The discount cannot be greater than the purchase total.');
                }
                $data['total_amount'] = round((float) $purchase->subtotal_amount + $tax - $discount, 2);
            }

            $purchase->update($data);

            return $this->findPurchase($purchase->fresh());
        });
    }

    public function receive(Purchase $purchase, User $user): array
    {
        return DB::transaction(function () use ($purchase, $user) {
            $purchase = Purchase::query()->lockForUpdate()->with('items')->findOrFail($purchase->id);
            if ($purchase->status !== 'draft') {
                throw ApiException::badRequest('This purchase has already been received or cancelled.');
            }
            if ($purchase->items->isEmpty()) {
                throw ApiException::badRequest('Add at least one item before receiving this purchase.');
            }

            foreach ($purchase->items as $line) {
                if (! $line->inventory_item_id) {
                    throw ApiException::badRequest("'{$line->item_name}' is not linked to a stock item.");
                }
                $costPerUnit = (float) $line->unit_cost;
                if ((float) $purchase->subtotal_amount > 0) {
                    $costPerUnit = $costPerUnit * (float) $purchase->total_amount / (float) $purchase->subtotal_amount;
                }
                $this->inventoryService->addPurchaseStock(
                    $line->inventory_item_id,
                    (float) $line->quantity,
                    $costPerUnit,
                    $purchase->id,
                    $purchase->purchase_number,
                    $user->id,
                    $purchase->branch_id,
                );
            }

            $purchase->update(['status' => 'received', 'received_at' => now()]);

            return $this->findPurchase($purchase->fresh());
        });
    }

    public function cancelDraft(Purchase $purchase): array
    {
        if ($purchase->status !== 'draft') {
            throw ApiException::badRequest('Only draft purchases can be cancelled.');
        }

        $purchase->update(['status' => 'cancelled']);

        return $this->findPurchase($purchase->fresh());
    }

    public function deleteDraft(Purchase $purchase): void
    {
        if ($purchase->status !== 'draft' || $purchase->payments()->exists()) {
            throw ApiException::badRequest('Only unpaid draft purchases can be deleted.');
        }

        $purchase->delete();
    }

    public function addPayment(Purchase $purchase, array $data, User $user): array
    {
        return DB::transaction(function () use ($purchase, $data, $user) {
            $purchase = Purchase::query()->lockForUpdate()->withSum('payments', 'amount')->findOrFail($purchase->id);
            if ($purchase->status !== 'received') {
                throw ApiException::badRequest('Receive this purchase before recording a payment.');
            }

            $paid = (float) ($purchase->payments_sum_amount ?? 0);
            $due = max(0, round((float) $purchase->total_amount - $paid, 2));
            $amount = (float) $data['amount'];
            if ($amount > $due + 0.001) {
                throw ApiException::badRequest('The payment is greater than the amount still owed.');
            }

            PurchasePayment::create([
                'purchase_id' => $purchase->id,
                'created_by_user_id' => $user->id,
                'amount' => $amount,
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $this->findPurchase($purchase->fresh());
        });
    }

    public function summary(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $receivedPurchasesThisMonth = Purchase::query()
            ->where('status', 'received')
            ->whereDate('purchase_date', '>=', $monthStart)
            ->whereDate('purchase_date', '<=', now()->toDateString())
            ->get(['id', 'total_amount']);
        $allReceivedPurchases = Purchase::query()
            ->where('status', 'received')
            ->withSum('payments as paid_amount', 'amount')
            ->get(['id', 'total_amount']);

        $totalExpenses = (float) DB::table('expenses')
            ->where('restaurant_id', Tenant::id())
            ->where('is_void', false)
            ->whereDate('expense_date', '>=', $monthStart)
            ->whereDate('expense_date', '<=', now()->toDateString())
            ->sum('amount');
        $stockSummary = $this->inventoryService->summary();

        return [
            ...$stockSummary,
            'purchases_this_month' => round($receivedPurchasesThisMonth->sum(fn ($purchase) => (float) $purchase->total_amount), 2),
            'amount_owed_to_suppliers' => round($allReceivedPurchases->sum(fn ($purchase) => max(0, (float) $purchase->total_amount - (float) ($purchase->paid_amount ?? 0))), 2),
            'expenses_this_month' => round($totalExpenses, 2),
        ];
    }

    protected function prepareLines(array $requestedLines): array
    {
        $lines = [];
        $subtotal = 0;

        foreach ($requestedLines as $line) {
            $item = InventoryItem::where('is_active', true)->findOrFail($line['inventory_item_id']);
            $quantity = (float) $line['quantity'];
            $unitCost = (float) $line['unit_cost'];
            $lineTotal = round($quantity * $unitCost, 2);
            $subtotal += $lineTotal;
            $lines[] = [
                'inventory_item_id' => $item->id,
                'item_name' => $item->name,
                'unit' => $item->unit,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
            ];
        }

        return [$lines, round($subtotal, 2)];
    }

    protected function findSupplier(?int $id): ?Supplier
    {
        return $id ? Supplier::where('is_active', true)->findOrFail($id) : null;
    }

    protected function findBranch(?int $id): ?Branch
    {
        return $id ? Branch::findOrFail($id) : null;
    }

    protected function presentPurchase(Purchase $purchase): array
    {
        $purchase->loadMissing(['supplier:id,name', 'branch:id,name', 'items.inventoryItem:id,name,unit', 'payments']);
        $paid = round((float) $purchase->payments->sum('amount'), 2);
        $total = (float) $purchase->total_amount;

        return [
            ...$purchase->toArray(),
            'paid_amount' => $paid,
            'due_amount' => max(0, round($total - $paid, 2)),
            'payment_status' => $paid <= 0 ? 'unpaid' : ($paid + 0.001 >= $total ? 'paid' : 'part_paid'),
        ];
    }
}
