<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\DiningTable;
use App\Support\Tenant;

class TableService
{
    public function list(?string $status = null, ?int $branchId = null)
    {
        return DiningTable::with('branch:id,name')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('table_number')
            ->get();
    }

    public function create(array $data): DiningTable
    {
        $branchId = (int) $data['branch_id'];

        $this->assertNoDuplicateNumber(
            number: $data['table_number'],
            branchId: $branchId
        );

        return DiningTable::create([
            'restaurant_id' => Tenant::id(),
            'branch_id' => $branchId,
            'table_number' => $data['table_number'],
            'capacity' => $data['capacity'] ?? 4,
        ]);
    }

    public function update(DiningTable $table, array $data): DiningTable
    {
        if (! empty($data['table_number'])) {
            $this->assertNoDuplicateNumber($data['table_number'], $data['branch_id']);
        }

        $table->update($data);

        return $table;
    }

    public function updateStatus(DiningTable $table, string $status): DiningTable
    {
        $table->update(['status' => $status]);

        return $table;
    }

    public function delete(DiningTable $table): void
    {
        $table->delete();
    }

    public function regenerateQrToken(DiningTable $table): DiningTable
    {
        $table->regenerateQrToken();

        return $table;
    }

    protected function assertNoDuplicateNumber(
    string $number,
    ?int $branchId,
    ?int $excludeId = null
    ): void {
        $query = DiningTable::query()
            ->where('restaurant_id', Tenant::id())
            ->where('branch_id', $branchId)
            ->where('table_number', $number);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ApiException::conflict(
                "Table '{$number}' already exists in this branch."
            );
        }
    }
}