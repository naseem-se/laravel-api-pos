<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AdjustInventoryRequest;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Inventory\UpdateInventoryItemRequest;
use App\Http\Requests\Inventory\UpdateMenuRecipeRequest;
use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Services\InventoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use ApiResponse;

    public function __construct(protected InventoryService $inventoryService) {}

    public function summary()
    {
        return $this->success($this->inventoryService->summary());
    }

    public function index(Request $request)
    {
        $items = $this->inventoryService->list([
            'search' => $request->query('search'),
            'low_stock' => $request->boolean('low_stock'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
            'per_page' => $request->query('per_page', $request->query('perPage')),
        ]);

        if (method_exists($items, 'total')) {
            return $this->success($items->items(), extra: ['pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ]]);
        }

        return $this->success($items);
    }

    public function store(StoreInventoryItemRequest $request)
    {
        return $this->success($this->inventoryService->create($request->validated(), $request->user()->id), 201);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem)
    {
        return $this->success($this->inventoryService->update($inventoryItem, $request->validated()));
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $this->inventoryService->delete($inventoryItem);

        return $this->message('Stock item archived');
    }

    public function adjust(AdjustInventoryRequest $request, InventoryItem $inventoryItem)
    {
        return $this->success($this->inventoryService->adjust($inventoryItem, $request->validated(), $request->user()->id));
    }

    public function transactions(InventoryItem $inventoryItem)
    {
        return $this->success($this->inventoryService->transactions($inventoryItem));
    }

    public function recipe(MenuItem $menuItem)
    {
        return $this->success($this->inventoryService->recipe($menuItem));
    }

    public function updateRecipe(UpdateMenuRecipeRequest $request, MenuItem $menuItem)
    {
        return $this->success($this->inventoryService->updateRecipe($menuItem, $request->validated()['items']));
    }
}