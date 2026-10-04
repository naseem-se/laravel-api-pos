<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\CheckoutOrderRequest;
use App\Http\Requests\Order\UpdateOrderItemsRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request)
    {
        $filters = [
            'status' => $request->query('status'),
            'order_type' => $request->query('order_type', $request->query('orderType')),
            'table_id' => $request->query('table_id', $request->query('tableId')),
            'branch_id' => $request->query('branch_id', $request->query('branchId')),
            'per_page' => $request->query('per_page', $request->query('perPage')),
            'search' => $request->query('search'),
        ];

        $orders = $this->orderService->list($filters);

        $extra = ['count' => method_exists($orders, 'total') ? $orders->total() : $orders->count()];
        if (isset($filters['per_page'])) {
            $extra['status_counts'] = $this->orderService->statusCounts($filters['branch_id'] ? (int) $filters['branch_id'] : null);
        }

        return $this->success(OrderResource::collection($orders), extra: $extra);
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->create($request->validated(), $request->user());

        return $this->success(new OrderResource($order), 201);
    }

    public function show(Order $order)
    {
        return $this->success(new OrderResource($this->orderService->find($order->id)));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $order = $this->orderService->transitionStatus($order, $request->validated()['status']);

        return $this->success(new OrderResource($order));
    }

    public function cancel(Order $order)
    {
        $order = $this->orderService->cancel($order);

        return $this->success(new OrderResource($order));
    }

    public function updateItems(UpdateOrderItemsRequest $request, Order $order)
    {
        $order = $this->orderService->updateItems($order, $request->validated()['items']);

        return $this->success(new OrderResource($order));
    }

    public function checkout(CheckoutOrderRequest $request, Order $order)
    {
        $order = $this->orderService->checkout($order, $request->validated());

        return $this->success(new OrderResource($order));
    }

    public function reallocateTable(Request $request, Order $order)
    {
        $data = $request->validate([
            'table_id' => ['required', 'integer', 'exists:tables,id'],
        ]);

        $order = $this->orderService->reallocateTable($order, $data['table_id']);

        return $this->success(new OrderResource($order));
    }
}