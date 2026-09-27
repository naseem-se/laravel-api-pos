<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Support\Facades\DB;

class StaffService
{
    public function list()
    {
        $restaurantId = Tenant::id();

        return User::query()
            ->with('branch:id,name')
            ->where('restaurant_id', $restaurantId)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['cashier', 'kitchen']);
            })
            ->latest()
            ->get();
    }

    public function create(array $data): User
    {
        if (! empty($data['branch_id'])) {
            $this->assertBranchBelongsToTenant($data['branch_id']);
        }

        return DB::transaction(function () use ($data) {
            $staff = User::create([
                'restaurant_id' => Tenant::id(),
                'branch_id' => $data['branch_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $staff->assignRole($data['role']);

            return $staff->load('branch:id,name');
        });
    }

    public function update(User $staff, array $data, \Illuminate\Http\Request $request): User
    {
        $this->assertBelongsToCurrentTenant($staff);
        $this->assertIsManageableStaff($staff);

        if (! empty($data['branch_id'])) {
            $this->assertBranchBelongsToTenant($data['branch_id']);
        }

        $updates = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
        ], fn ($v) => $v !== null);

        if ($request->exists('branch_id')) {
            $updates['branch_id'] = $data['branch_id'] ?? null;
        }

        $staff->update($updates);

        if (! empty($data['role'])) {
            $staff->syncRoles([$data['role']]);
        }

        return $staff->fresh('branch:id,name');
    }

    public function toggleStatus(User $staff, bool $isActive): User
    {
        $this->assertBelongsToCurrentTenant($staff);
        $this->assertIsManageableStaff($staff);

        $staff->update(['is_active' => $isActive]);

        return $staff->fresh('branch:id,name');
    }

    public function delete(User $staff): void
    {
        $this->assertBelongsToCurrentTenant($staff);
        $this->assertIsManageableStaff($staff);

        $hasOrders = Order::allRestaurants()->where('placed_by_user_id', $staff->id)->exists();

        if ($hasOrders) {
            throw ApiException::conflict(
                'This staff member has order history and cannot be deleted. Deactivate the account instead.'
            );
        }

        $staff->delete();
    }

    protected function assertBranchBelongsToTenant(int $branchId): void
    {
        if (! Branch::find($branchId)) {
            throw ApiException::badRequest('branchId does not belong to this restaurant');
        }
    }

    protected function assertBelongsToCurrentTenant(User $staff): void
    {
        if ($staff->restaurant_id !== Tenant::id()) {
            throw ApiException::notFound('Staff member not found');
        }
    }

    protected function assertIsManageableStaff(User $staff): void
    {
        if (! $staff->hasAnyRole(['cashier', 'kitchen'])) {
            throw ApiException::notFound('Staff member not found');
        }
    }
}