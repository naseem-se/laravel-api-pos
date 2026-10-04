<?php

namespace Tests\Feature;

use App\Http\Requests\Order\CheckoutOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\TableResource;
use App\Models\DiningTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OrderCheckoutRequestTest extends TestCase
{
    public function test_checkout_requires_a_supported_payment_method(): void
    {
        $rules = (new CheckoutOrderRequest())->rules();
        $valid = Validator::make(['payment_method' => 'card', 'discount_amount' => 5], $rules);
        $invalid = Validator::make(['payment_method' => 'voucher', 'discount_amount' => -1], $rules);

        $this->assertFalse($valid->fails());
        $this->assertTrue($invalid->fails());
    }

    public function test_order_creation_does_not_collect_payment_or_discount(): void
    {
        $rules = (new StoreOrderRequest())->rules();

        $this->assertArrayNotHasKey('payment_method', $rules);
        $this->assertArrayNotHasKey('discount_amount', $rules);
    }

    public function test_table_resource_includes_branch_id_for_table_selection(): void
    {
        $table = new DiningTable(['branch_id' => 12, 'table_number' => 'A1']);
        $resource = new TableResource($table);

        $this->assertSame(12, $resource->toArray(Request::create('/'))['branch_id']);
    }
}