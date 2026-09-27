<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use App\Support\Tenant;

class ReportsService
{

    public function bestSellers(Carbon $startDate): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.restaurant_id', Tenant::id())
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', '!=', 'cancelled')
            ->select('order_items.menu_item_id')
            ->selectRaw('MAX(order_items.name) as name')
            ->selectRaw('SUM(order_items.quantity) as quantity_sold')
            ->selectRaw('SUM(order_items.subtotal) as revenue')
            ->groupBy('order_items.menu_item_id')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'menu_item_id' => $row->menu_item_id,
                'name' => $row->name,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => (float) $row->revenue,
            ])
            ->toArray();
    }

    public function peakHours(Carbon $startDate): array
    {
        $results = Order::where('created_at', '>=', $startDate)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as order_count')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        $filled = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $filled[] = [
                'hour' => $hour,
                'order_count' => (int) ($results->get($hour)->order_count ?? 0),
            ];
        }

        return $filled;
    }

    public function revenueTrend(Carbon $startDate, int $days): array
    {
        $results = Order::where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as order_count')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($row) => $row->date);

        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $entry = $results->get($date);

            $trend[] = [
                'date' => $date,
                'revenue' => (float) ($entry->revenue ?? 0),
                'order_count' => (int) ($entry->order_count ?? 0),
            ];
        }

        return $trend;
    }

    public function orderTypeBreakdown(Carbon $startDate): array
    {
        return Order::where('created_at', '>=', $startDate)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('order_type, COUNT(*) as count, SUM(total_amount) as revenue')
            ->groupBy('order_type')
            ->get()
            ->map(fn ($row) => [
                'order_type' => $row->order_type,
                'count' => (int) $row->count,
                'revenue' => (float) $row->revenue,
            ])
            ->toArray();
    }

    public function summary(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        $revenueTrend = $this->revenueTrend(Carbon::parse($startDate), $days);
        $orderTypeBreakdown = $this->orderTypeBreakdown(Carbon::parse($startDate));

        return [
            'days' => $days,
            'best_sellers' => $this->bestSellers(Carbon::parse($startDate)),
            'peak_hours' => $this->peakHours(Carbon::parse($startDate)),
            'revenue_trend' => $revenueTrend,
            'order_type_breakdown' => $orderTypeBreakdown,
            'total_revenue' => array_sum(array_column($revenueTrend, 'revenue')),
            'total_orders' => array_sum(array_column($orderTypeBreakdown, 'count')),
        ];
    }


    public function exportRows(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        return Order::with(['table:id,table_number', 'branch:id,name', 'placedBy:id,name', 'items'])
            ->where('created_at', '>=', $startDate)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($order) => [
                'order_number' => $order->order_number,
                'date' => $order->created_at->format('Y-m-d'),
                'time' => $order->created_at->format('H:i'),
                'order_type' => $order->order_type,
                'branch' => $order->branch?->name ?? '',
                'table' => $order->table?->table_number ?? '',
                'placed_by' => $order->placedBy?->name ?? $order->customer_name ?? '',
                'item_count' => $order->items->sum('quantity'),
                'items' => $order->items->map(fn ($i) => "{$i->quantity}x {$i->name}")->join('; '),
                'subtotal' => (float) $order->subtotal_amount,
                'tax' => (float) $order->tax_amount,
                'discount' => (float) $order->discount_amount,
                'total' => (float) $order->total_amount,
                'status' => $order->status,
            ])
            ->toArray();
    }
}