<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreCategoryRequest;
use App\Http\Requests\Menu\UpdateCategoryRequest;
use App\Http\Resources\MenuCategoryResource;
use App\Models\MenuCategory;
use App\Services\MenuService;
use App\Traits\ApiResponse;

class MenuCategoryController extends Controller
{
    use ApiResponse;

    public function __construct(protected MenuService $menuService) {}

    public function index()
    {
        $categories = $this->menuService->listCategories();

        return $this->success(MenuCategoryResource::collection($categories), extra: ['count' => $categories->count()]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = $this->menuService->createCategory($request->validated());

        return $this->success(new MenuCategoryResource($category), 201);
    }

    public function update(UpdateCategoryRequest $request, MenuCategory $menuCategory)
    {
        $category = $this->menuService->updateCategory($menuCategory, $request->validated());

        return $this->success(new MenuCategoryResource($category));
    }

    public function destroy(MenuCategory $menuCategory)
    {
        $this->menuService->deleteCategory($menuCategory);

        return $this->message('Category deleted');
    }
}