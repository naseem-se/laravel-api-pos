<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\InventoryService;
use App\Services\PurchasingService;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryPurchasingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Restaurant $restaurant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::create(['name' => 'Test Cafe', 'slug' => 'test-cafe']);
        Tenant::set($this->restaurant->id);
        $this->user = User::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Test Owner',
            'email' => 'owner@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Tenant::clear();
        parent::tearDown();
    }

    public function test_stock_changes_are_logged_and_order_stock_can_be_returned(): void
    {
        $inventory = app(InventoryService::class);
        $stockItem = $inventory->create([
            'name' => 'Coffee beans',
            'unit' => 'g',
            'current_stock' => 100,
            'low_stock_threshold' => 20,
            'cost_per_unit' => 0.02,
        ], $this->user->id);

        $category = MenuCategory::create(['name' => 'Drinks']);
        $menuItem = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Coffee',
            'price' => 500,
            'is_available' => true,
        ]);
        $inventory->updateRecipe($menuItem, [[
            'inventory_item_id' => $stockItem->id,
            'quantity_per_item' => 15,
        ]]);

        $order = Order::create([
            'order_number' => 1,
            'order_type' => 'takeaway',
            'status' => 'pending',
            'placed_by_user_id' => $this->user->id,
            'subtotal_amount' => 500,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 500,
        ]);
        $order->items()->create([
            'menu_item_id' => $menuItem->id,
            'name' => 'Coffee',
            'price' => 500,
            'quantity' => 2,
            'subtotal' => 1000,
        ]);

        $inventory->deductForOrder($order);
        $this->assertSame('70.0000', $stockItem->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $stockItem->id,
            'type' => 'order_use',
            'quantity_change' => -30,
        ]);

        $inventory->restoreOrderStock($order);
        $this->assertSame('100.0000', $stockItem->fresh()->current_stock);
        $this->assertSame(3, $stockItem->transactions()->count());
    }

    public function test_receiving_a_purchase_updates_stock_cost_and_supplier_balance(): void
    {
        $inventory = app(InventoryService::class);
        $stockItem = $inventory->create([
            'name' => 'Milk',
            'unit' => 'l',
            'current_stock' => 10,
            'low_stock_threshold' => 2,
            'cost_per_unit' => 2,
        ], $this->user->id);
        $purchasing = app(PurchasingService::class);
        $supplier = $purchasing->createSupplier(['name' => 'Local Dairy']);

        $purchase = $purchasing->createPurchase([
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-09-26',
            'tax_amount' => 0,
            'discount_amount' => 0,
            'items' => [[
                'inventory_item_id' => $stockItem->id,
                'quantity' => 10,
                'unit_cost' => 4,
            ]],
        ], $this->user);

        $received = $purchasing->receive(Purchase::findOrFail($purchase['id']), $this->user);
        $this->assertSame('received', $received['status']);
        $this->assertSame(20.0, (float) $stockItem->fresh()->current_stock);
        $this->assertEqualsWithDelta(3.0, (float) $stockItem->fresh()->cost_per_unit, 0.0001);

        $paid = $purchasing->addPayment(Purchase::findOrFail($purchase['id']), [
            'amount' => 20,
            'method' => 'cash',
            'paid_at' => '2026-09-26',
        ], $this->user);
        $this->assertSame('part_paid', $paid['payment_status']);
        $this->assertSame(20.0, (float) $paid['due_amount']);
    }

    public function test_voiding_an_expense_preserves_it_but_removes_it_from_totals(): void
    {
        $expenses = app(ExpenseService::class);
        $expense = $expenses->create([
            'category' => 'Utilities',
            'description' => 'Electricity bill',
            'amount' => 2500,
            'payment_method' => 'bank_transfer',
            'expense_date' => '2026-09-26',
        ], $this->user);

        $this->assertSame(2500.0, (float) $expenses->summary()['total_amount']);
        $expenses->void($expense, $this->user);

        $this->assertSame(0.0, (float) $expenses->summary()['total_amount']);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'is_void' => true]);
    }
}
