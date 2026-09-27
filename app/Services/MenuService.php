<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;

class MenuService
{
    // --- Categories ---

    public function listCategories()
    {
        return MenuCategory::orderBy('display_order')->orderBy('created_at')->get();
    }

    public function createCategory(array $data): MenuCategory
    {
        $this->assertNoDuplicateCategoryName($data['name']);

        return MenuCategory::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'display_order' => $data['display_order'] ?? 0,
        ]);
    }

    public function updateCategory(MenuCategory $category, array $data): MenuCategory
    {
        if (! empty($data['name'])) {
            $this->assertNoDuplicateCategoryName($data['name'], excludeId: $category->id);
        }

        $category->update($data);

        return $category;
    }

    public function deleteCategory(MenuCategory $category): void
    {
        $itemCount = $category->items()->count();

        if ($itemCount > 0) {
            throw ApiException::badRequest(
                "This category has {$itemCount} menu items. Move or delete them before deleting the category."
            );
        }

        $category->delete();
    }

    protected function assertNoDuplicateCategoryName(string $name, ?int $excludeId = null): void
    {
        $query = MenuCategory::whereRaw('LOWER(name) = ?', [strtolower($name)]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ApiException::conflict("Category '{$name}' already exists");
        }
    }

    // --- Items ---

    public function listItems(?int $categoryId = null, ?bool $isAvailable = null)
    {
        return MenuItem::with(['category:id,name', 'modifierGroups.options'])
            ->when($categoryId, fn ($q) => $q->where('menu_category_id', $categoryId))
            ->when(! is_null($isAvailable), fn ($q) => $q->where('is_available', $isAvailable))
            ->orderByDesc('created_at')
            ->get();
    }

    public function createItem(array $data): MenuItem
    {
        return DB::transaction(function () use ($data) {
            $item = MenuItem::create([
                'menu_category_id' => $data['menu_category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'price' => $data['price'],
                'image_url' => $data['image_url'] ?? null,
                'preparation_time_minutes' => $data['preparation_time_minutes'] ?? 10,
            ]);

            $this->syncModifierGroups($item, $data['modifier_groups'] ?? []);

            return $item->load(['category:id,name', 'modifierGroups.options']);
        });
    }

    public function updateItem(MenuItem $item, array $data): MenuItem
    {
        return DB::transaction(function () use ($item, $data) {
            $item->update(array_filter([
                'menu_category_id' => $data['menu_category_id'] ?? null,
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? null,
                'image_url' => array_key_exists('image_url', $data) ? $data['image_url'] : $item->image_url,
                'preparation_time_minutes' => $data['preparation_time_minutes'] ?? null,
                'is_available' => array_key_exists('is_available', $data) ? $data['is_available'] : $item->is_available,
            ], fn ($v) => $v !== null));

            if (array_key_exists('modifier_groups', $data)) {
                $this->syncModifierGroups($item, $data['modifier_groups'] ?? []);
            }

            return $item->fresh(['category:id,name', 'modifierGroups.options']);
        });
    }

    public function toggleAvailability(MenuItem $item, bool $isAvailable): MenuItem
    {
        $item->update(['is_available' => $isAvailable]);

        return $item;
    }

    public function deleteItem(MenuItem $item): void
    {
        $item->delete(); // modifier groups/options cascade via FK
    }

    // Replace-all strategy: delete every existing group (cascades to
    // its options via the FK), recreate from what was submitted. Far
    // simpler and less error-prone than diffing which groups/options
    // changed, and matches exactly what the frontend already sends —
    // the full current state on every save, not a delta.
    protected function syncModifierGroups(MenuItem $item, array $groups): void
    {
        $item->modifierGroups()->delete();

        foreach ($groups as $groupIndex => $groupData) {
            $group = $item->modifierGroups()->create([
                'name' => $groupData['name'],
                'selection_type' => $groupData['selection_type'],
                'pricing_mode' => $groupData['pricing_mode'],
                'is_required' => $groupData['is_required'] ?? false,
                'display_order' => $groupIndex,
            ]);

            foreach ($groupData['options'] as $optionIndex => $optionData) {
                $group->options()->create([
                    'name' => $optionData['name'],
                    'price_delta' => $optionData['price_delta'],
                    'display_order' => $optionIndex,
                ]);
            }
        }
    }
}