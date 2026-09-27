<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Branch;
use App\Models\DiningTable;
use App\Models\Plan;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\Tenant;

class BranchService
{
    public function list()
    {
        return Branch::orderByDesc('is_main_branch')->orderBy('created_at')->get();
    }

    public function create(array $data): Branch
    {
        $restaurant = Restaurant::allRestaurants()->find(Tenant::id());
        // Always read the LIVE plan limit, never a stale denormalized
        // value — same principle as the Node version's fix: editing a
        // plan's max_branches in superadmin must take effect
        // immediately for every restaurant on that plan.
        $plan = Plan::where('slug', $restaurant->subscription_plan)->first();
        $maxBranches = $plan?->max_branches ?? 1;

        $currentCount = Branch::count();

        if ($currentCount >= $maxBranches) {
            throw ApiException::paymentRequired(
                "Your current plan allows up to {$maxBranches} branch(es). Upgrade your plan to add more."
            );
        }

        return Branch::create([
            'name' => $data['name'],
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? '',
            'is_main_branch' => $currentCount === 0,
        ]);
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);

        return $branch;
    }

    public function delete(Branch $branch): void
    {
        $tableCount = DiningTable::where('branch_id', $branch->id)->count();
        $staffCount = User::where('branch_id', $branch->id)->count();

        if ($tableCount > 0 || $staffCount > 0) {
            throw ApiException::conflict(
                "This branch has {$tableCount} table(s) and {$staffCount} staff member(s) assigned. Reassign them before deleting."
            );
        }

        $branch->delete();
    }
}