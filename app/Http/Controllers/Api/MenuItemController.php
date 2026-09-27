<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuItemRequest;
use App\Http\Requests\Menu\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Services\MenuService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    use ApiResponse;

    public function __construct(protected MenuService $menuService) {}

    public function index(Request $request)
    {
        $items = $this->menuService->listItems(
            categoryId: $request->query('category_id'),
            isAvailable: $request->has('is_available') ? filter_var($request->query('is_available'), FILTER_VALIDATE_BOOLEAN) : null,
        );

        return $this->success(MenuItemResource::collection($items), extra: ['count' => $items->count()]);
    }

    public function store(StoreMenuItemRequest $request)
    {
        $item = $this->menuService->createItem($request->validated());

        return $this->success(new MenuItemResource($item), 201);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem)
    {
        $item = $this->menuService->updateItem($menuItem, $request->validated());

        return $this->success(new MenuItemResource($item));
    }

    public function toggleAvailability(Request $request, MenuItem $menuItem)
    {
        $request->validate(['is_available' => ['required', 'boolean']]);

        $item = $this->menuService->toggleAvailability($menuItem, $request->boolean('is_available'));

        return $this->success(new MenuItemResource($item));
    }

    public function destroy(MenuItem $menuItem)
    {
        $this->menuService->deleteItem($menuItem);

        return $this->message('Menu item deleted');
    }
}