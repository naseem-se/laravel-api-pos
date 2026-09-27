<?php

namespace Tests\Feature;

use App\Http\Requests\Menu\StoreMenuItemRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class MenuItemRequestTest extends TestCase
{
    public function test_category_id_is_mapped_to_menu_category_id_during_validation(): void
    {
        $request = StoreMenuItemRequest::create('/api/v1/menu-items', 'POST', [
            'name' => 'Pizza',
            'description' => '',
            'price' => 999,
            'category_id' => 1,
            'preparation_time_minutes' => 30,
            'image_url' => 'http://localhost:8000/storage/menu-items/example.png',
            'modifier_groups' => [],
        ]);

        $method = new ReflectionMethod(StoreMenuItemRequest::class, 'prepareForValidation');
        $method->invoke($request);

        $this->assertSame(1, $request->input('menu_category_id'));
        $this->assertSame(1, $request->input('category_id'));
    }

    public function test_order_request_accepts_customer_name_and_notes(): void
    {
        $rules = (new StoreOrderRequest())->rules();

        $this->assertArrayHasKey('customer_name', $rules);
        $this->assertArrayHasKey('notes', $rules);
    }

    public function test_menu_image_url_uses_current_api_host_for_storage_urls(): void
    {
        $request = Request::create('https://api.example.test/api/v1/menu-items');
        $legacyItem = new MenuItem();
        $legacyItem->image_url = 'http://localhost:8000/storage/menu-items/example.png';
        $resource = new MenuItemResource($legacyItem);

        $this->assertSame(
            'https://api.example.test/storage/menu-items/example.png',
            $resource->toArray($request)['image_url']
        );

        $relativeItem = new MenuItem();
        $relativeItem->image_url = '/storage/menu-items/example.png';
        $relativeResource = new MenuItemResource($relativeItem);
        $this->assertSame(
            'https://api.example.test/storage/menu-items/example.png',
            $relativeResource->toArray($request)['image_url']
        );
    }

    public function test_menu_image_url_preserves_external_hosts(): void
    {
        $request = Request::create('https://api.example.test/api/v1/menu-items');
        $imageUrl = 'https://cdn.example.test/storage/menu-items/example.png';
        $item = new MenuItem();
        $item->image_url = $imageUrl;
        $resource = new MenuItemResource($item);

        $this->assertSame($imageUrl, $resource->toArray($request)['image_url']);
    }
}
