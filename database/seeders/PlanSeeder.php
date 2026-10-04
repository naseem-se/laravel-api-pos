<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free', 'name' => 'Free', 'price_monthly' => 0,
                'max_branches' => 1, 'max_orders_per_month' => 200,
                'features' => ['1 branch', '200 orders/month'], 'display_order' => 0,
                'kds_enabled' => false, 'online_ordering_enabled' => false,
            ],
            [
                'slug' => 'basic', 'name' => 'Basic', 'price_monthly' => 4999,
                'max_branches' => 1, 'max_orders_per_month' => 2000,
                'features' => ['1 branch', '2,000 orders/month', 'Kitchen Display System'], 'display_order' => 1,
                'kds_enabled' => true, 'online_ordering_enabled' => false,
            ],
            [
                'slug' => 'pro', 'name' => 'Pro', 'price_monthly' => 9999,
                'max_branches' => 5, 'max_orders_per_month' => null,
                'features' => ['5 branches', 'Unlimited orders', 'KDS'], 'display_order' => 2,
                'kds_enabled' => true, 'online_ordering_enabled' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}