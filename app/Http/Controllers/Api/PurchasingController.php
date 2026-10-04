<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchasePaymentRequest;
use App\Http\Requests\Purchasing\StorePurchaseRequest;
use App\Http\Requests\Purchasing\StoreSupplierRequest;
use App\Http\Requests\Purchasing\UpdatePurchaseRequest;
use App\Http\Requests\Purchasing\UpdateSupplierRequest;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchasingService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PurchasingController extends Controller
{
    use ApiResponse;

    public function __construct(protected PurchasingService $purchasingService) {}

    public function suppliers()
    {
        return $this->success($this->purchasingService->suppliers());
    }

    public function storeSupplier(StoreSupplierRequest $request)
    {
        return $this->success($this->purchasingService->createSupplier($request->validated()), 201);
    }

    public function updateSupplier(UpdateSupplierRequest $request, Supplier $supplier)
    {
        return $this->success($this->purchasingService->updateSupplier($supplier, $request->validated()));
    }

    public function summary()
    {
        return $this->success($this->purchasingService->summary());
    }

    public function index(Request $request)
    {
        $purchases = $this->purchasingService->purchases([
            'status' => $request->query('status'),
            'supplier_id' => $request->query('supplier_id'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'per_page' => $request->query('per_page', $request->query('perPage')),
        ]);

        if (method_exists($purchases, 'total')) {
            return $this->success($purchases->items(), extra: ['pagination' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'total' => $purchases->total(),
            ]]);
        }

        return $this->success($purchases);
    }

    public function store(StorePurchaseRequest $request)
    {
        return $this->success($this->purchasingService->createPurchase($request->validated(), $request->user()), 201);
    }

    public function show(Purchase $purchase)
    {
        return $this->success($this->purchasingService->findPurchase($purchase));
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        return $this->success($this->purchasingService->updateDraft($purchase, $request->validated()));
    }

    public function receive(Purchase $purchase, Request $request)
    {
        return $this->success($this->purchasingService->receive($purchase, $request->user()));
    }

    public function cancel(Purchase $purchase)
    {
        return $this->success($this->purchasingService->cancelDraft($purchase));
    }

    public function payment(StorePurchasePaymentRequest $request, Purchase $purchase)
    {
        return $this->success($this->purchasingService->addPayment($purchase, $request->validated(), $request->user()), 201);
    }

    public function destroy(Purchase $purchase)
    {
        $this->purchasingService->deleteDraft($purchase);

        return $this->message('Draft purchase deleted');
    }
}