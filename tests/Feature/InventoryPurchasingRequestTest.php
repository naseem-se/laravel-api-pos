<?php

namespace Tests\Feature;

use App\Http\Requests\Inventory\AdjustInventoryRequest;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Purchasing\StorePurchasePaymentRequest;
use App\Http\Requests\Purchasing\StorePurchaseRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class InventoryPurchasingRequestTest extends TestCase
{
    public function test_stock_item_requires_a_name_unit_and_non_negative_levels(): void
    {
        $rules = (new StoreInventoryItemRequest())->rules();
        $valid = Validator::make([
            'name' => 'Coffee beans',
            'unit' => 'g',
            'current_stock' => 2.5,
            'low_stock_threshold' => 1,
            'cost_per_unit' => 0.02,
        ], $rules);
        $negativeStock = Validator::make([
            'name' => 'Coffee beans',
            'unit' => 'g',
            'current_stock' => -1,
            'low_stock_threshold' => 1,
            'cost_per_unit' => 0.02,
        ], $rules);

        $this->assertFalse($valid->fails());
        $this->assertTrue($negativeStock->fails());
    }

    public function test_stock_adjustment_requires_a_reason_and_positive_quantity(): void
    {
        $rules = (new AdjustInventoryRequest())->rules();
        $missingReason = Validator::make([
            'direction' => 'remove',
            'quantity' => 1,
        ], $rules);
        $validAdjustment = Validator::make([
            'direction' => 'add',
            'quantity' => 1.25,
            'notes' => 'Count correction',
        ], $rules);

        $this->assertTrue($missingReason->fails());
        $this->assertFalse($validAdjustment->fails());
    }

    public function test_purchase_requires_at_least_one_item_with_positive_quantity(): void
    {
        $rules = (new StorePurchaseRequest())->rules();
        $emptyPurchase = Validator::make([
            'purchase_date' => '2026-09-26',
            'items' => [],
        ], $rules);
        $incompleteLine = Validator::make([
            'purchase_date' => '2026-09-26',
            'items' => [[
                'quantity' => 0,
                'unit_cost' => 5.50,
            ]],
        ], $rules);

        $this->assertTrue($emptyPurchase->fails());
        $this->assertTrue($incompleteLine->fails());
        $this->assertTrue($incompleteLine->errors()->has('items.0.inventory_item_id'));
        $this->assertTrue($incompleteLine->errors()->has('items.0.quantity'));
    }

    public function test_purchase_payment_requires_valid_amount_method_and_date(): void
    {
        $rules = (new StorePurchasePaymentRequest())->rules();
        $validPayment = Validator::make([
            'amount' => 10,
            'method' => 'bank_transfer',
            'paid_at' => '2026-09-26',
        ], $rules);
        $invalidPayment = Validator::make([
            'amount' => 0,
            'method' => 'crypto',
            'paid_at' => 'not-a-date',
        ], $rules);

        $this->assertFalse($validPayment->fails());
        $this->assertTrue($invalidPayment->fails());
    }
}
