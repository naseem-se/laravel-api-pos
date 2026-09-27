<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function list(array $filters = [])
    {
        return Expense::query()
            ->with(['branch:id,name', 'createdBy:id,name', 'voidedBy:id,name'])
            ->when(! ($filters['include_voided'] ?? false), fn ($query) => $query->where('is_void', false))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['branch_id'] ?? null, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('expense_date', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('expense_date', '<=', $date))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('description', 'like', '%'.$search.'%')
                    ->orWhere('vendor', 'like', '%'.$search.'%')
                    ->orWhere('reference', 'like', '%'.$search.'%');
            }))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();
    }

    public function create(array $data, User $user): Expense
    {
        $data['branch_id'] = $this->validBranchId($data['branch_id'] ?? null);

        return Expense::create([
            ...$data,
            'created_by_user_id' => $user->id,
            'is_recurring' => $data['is_recurring'] ?? false,
            'is_void' => false,
        ])->load(['branch:id,name', 'createdBy:id,name']);
    }

    public function update(Expense $expense, array $data): Expense
    {
        if ($expense->is_void) {
            throw ApiException::badRequest('Voided expenses cannot be changed. Add a new expense instead.');
        }
        if (array_key_exists('branch_id', $data)) {
            $data['branch_id'] = $this->validBranchId($data['branch_id']);
        }

        $expense->update($data);

        return $expense->fresh(['branch:id,name', 'createdBy:id,name']);
    }

    public function void(Expense $expense, User $user): Expense
    {
        if ($expense->is_void) {
            throw ApiException::badRequest('This expense has already been voided.');
        }

        $expense->update([
            'is_void' => true,
            'voided_at' => now(),
            'voided_by_user_id' => $user->id,
        ]);

        return $expense->fresh(['branch:id,name', 'createdBy:id,name', 'voidedBy:id,name']);
    }

    public function summary(array $filters = []): array
    {
        $query = Expense::query()->where('is_void', false);
        if (! empty($filters['from'])) $query->whereDate('expense_date', '>=', $filters['from']);
        if (! empty($filters['to'])) $query->whereDate('expense_date', '<=', $filters['to']);
        if (! empty($filters['branch_id'])) $query->where('branch_id', $filters['branch_id']);

        $rows = (clone $query)->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as entry_count'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return [
            'total_amount' => round((float) (clone $query)->sum('amount'), 2),
            'entry_count' => (int) (clone $query)->count(),
            'by_category' => $rows->map(fn ($row) => [
                'category' => $row->category,
                'total' => (float) $row->total,
                'entry_count' => (int) $row->entry_count,
            ])->all(),
        ];
    }

    protected function validBranchId(?int $branchId): ?int
    {
        if ($branchId) Branch::findOrFail($branchId);

        return $branchId;
    }
}
