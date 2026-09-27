<?php

// namespace App\Services;

// use App\Models\DiningTable;
// use App\Models\Order;
// use Illuminate\Support\Carbon;

// class DashboardService
// {
//     public function stats(): array
//     {
//         $startOfDay = Carbon::today();

//         $todaysOrderCount = Order::where('created_at', '>=', $startOfDay)->count();

//         $todaysRevenue = Order::where('created_at', '>=', $startOfDay)
//             ->where('status', 'completed')
//             ->sum('total_amount');

//         $activeTablesCount = DiningTable::where('status', 'occupied')->count();

//         $recentOrders = Order::orderByDesc('created_at')
//             ->limit(5)
//             ->get(['id', 'order_number', 'order_type', 'status', 'total_amount', 'created_at']);

//         return [
//             'todays_order_count' => $todaysOrderCount,
//             'todays_revenue' => (float) $todaysRevenue,
//             'active_tables_count' => $activeTablesCount,
//             'low_stock_items' => null,
//             'recent_orders' => $recentOrders,
//         ];
//     }
// }


namespace App\Services;

use App\Models\DiningTable;
use App\Models\Order;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    /**
     * Roles that are restricted to their assigned branch.
     */
    private const BRANCH_ROLES = [
        'cashier',
        'kitchen',
    ];

    /**
     * Get dashboard statistics for the authenticated user.
     *
     * Owner:
     * - Can see all branches belonging to the restaurant.
     *
     * Cashier / Kitchen:
     * - Can only see their assigned branch.
     */
    public function stats(): array
    {
        $user = Auth::user();
        $restaurantId = Tenant::id();
        $startOfDay = Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | Base Queries
        |--------------------------------------------------------------------------
        */

        $ordersQuery = $this->scopedOrders($user, $restaurantId);

        $tablesQuery = $this->scopedTables($user, $restaurantId);

        /*
        |--------------------------------------------------------------------------
        | Today's Statistics
        |--------------------------------------------------------------------------
        */

        $todaysOrdersQuery = (clone $ordersQuery)
            ->where('created_at', '>=', $startOfDay);

        $todaysOrderCount = (clone $todaysOrdersQuery)
            ->count();

        $todaysRevenue = (clone $todaysOrdersQuery)
            ->where('status', 'completed')
            ->sum('total_amount');

        $completedTodayCount = (clone $todaysOrdersQuery)
            ->where('status', 'completed')
            ->count();

        $avgTicket = $completedTodayCount > 0
            ? round(((float) $todaysRevenue) / $completedTodayCount, 2)
            : 0;

        $pipeline = [];
        foreach (['pending', 'preparing', 'ready', 'served'] as $status) {
            $pipeline[$status] = (clone $ordersQuery)
                ->where('status', $status)
                ->count();
        }

        $openOrdersCount = array_sum($pipeline);

        /*
        |--------------------------------------------------------------------------
        | Active Tables
        |--------------------------------------------------------------------------
        */

        $activeTablesCount = $tablesQuery
            ->where('status', 'occupied')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent Orders
        |--------------------------------------------------------------------------
        */

        $recentOrders = (clone $ordersQuery)
            ->with([
                'table:id,table_number',
                'items:id,order_id,name,quantity,notes',
            ])
            ->select([
                'id',
                'order_number',
                'order_type',
                'status',
                'total_amount',
                'table_id',
                'notes',
                'created_at',
            ])
            ->latest('created_at')
            ->limit(8)
            ->get();

        $kitchenQueue = (clone $ordersQuery)
            ->with([
                'table:id,table_number',
                'items:id,order_id,name,quantity,notes',
            ])
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->orderBy('created_at')
            ->limit(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return [
            'todays_order_count' => (int) $todaysOrderCount,

            'todays_revenue' => (float) $todaysRevenue,

            'avg_ticket' => (float) $avgTicket,

            'completed_today_count' => (int) $completedTodayCount,

            'open_orders_count' => (int) $openOrdersCount,

            'pipeline' => $pipeline,

            'active_tables_count' => (int) $activeTablesCount,

            'low_stock_items' => null,

            'recent_orders' => $recentOrders,

            'kitchen_queue' => $kitchenQueue,
        ];
    }

    /**
     * Base Order query scoped to the current tenant/user.
     */
    private function scopedOrders(User $user, int|string $restaurantId): Builder
    {
        $query = Order::query()
            ->where('restaurant_id', $restaurantId);

        /*
        |--------------------------------------------------------------------------
        | Branch-level staff
        |--------------------------------------------------------------------------
        */

        if ($user->hasAnyRole(self::BRANCH_ROLES)) {
            /*
             * Never allow branch staff without an assigned branch
             * to see restaurant-wide data.
             */
            if (empty($user->branch_id)) {
                $query->whereRaw('1 = 0');

                return $query;
            }

            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    /**
     * Base DiningTable query scoped to the current tenant/user.
     */
    private function scopedTables(User $user, int|string $restaurantId): Builder
    {
        $query = DiningTable::query()
            ->where('restaurant_id', $restaurantId);

        /*
        |--------------------------------------------------------------------------
        | Branch-level staff
        |--------------------------------------------------------------------------
        */

        if ($user->hasAnyRole(self::BRANCH_ROLES)) {
            /*
             * Never allow branch staff without an assigned branch
             * to see restaurant-wide table data.
             */
            if (empty($user->branch_id)) {
                $query->whereRaw('1 = 0');

                return $query;
            }

            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}