<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\DriverTextPrintService;
use Tests\TestCase;

class DriverTextPrintServiceTest extends TestCase
{
    public function test_receipt_uses_printable_text_without_escpos_bytes(): void
    {
        $restaurant = new Restaurant(['name' => 'Cafe Example']);
        $restaurant->settings = ['receiptPaperWidth' => '58mm'];

        $item = new OrderItem([
            'name' => "Long item name with control\x1Bdata that wraps across lines",
            'quantity' => 2,
            'subtotal' => 1500,
        ]);
        $item->setRelation('modifiers', collect());

        $order = new Order([
            'order_number' => 42,
            'order_type' => 'takeaway',
            'subtotal_amount' => 1500,
            'tax_amount' => 0,
            'discount_amount' => 100,
            'total_amount' => 1400,
            'payment_method' => 'cash',
        ]);
        $order->created_at = now();
        $order->setRelation('table', null);
        $order->setRelation('items', collect([$item]));

        $receipt = (new DriverTextPrintService())->buildReceipt($order, $restaurant);

        $this->assertStringContainsString('Cafe Example', $receipt);
        $this->assertStringContainsString('Order #42', $receipt);
        $this->assertStringContainsString('TOTAL', $receipt);
        $this->assertStringContainsString('Paid by', $receipt);
        $this->assertStringNotContainsString("\x1B", $receipt);
        $this->assertStringNotContainsString("\x1D", $receipt);
    }

    public function test_kitchen_ticket_formats_order_items_and_notes_as_text(): void
    {
        $restaurant = new Restaurant(['name' => 'Cafe Example']);
        $restaurant->settings = ['receiptPaperWidth' => '80mm'];

        $item = new OrderItem(['name' => 'Pasta', 'quantity' => 1, 'notes' => 'No onions']);
        $item->setRelation('modifiers', collect());

        $order = new Order(['order_number' => 17, 'order_type' => 'dine-in', 'notes' => 'Serve quickly']);
        $order->created_at = now();
        $order->setRelation('table', null);
        $order->setRelation('items', collect([$item]));

        $ticket = (new DriverTextPrintService())->buildKitchenTicket($order, $restaurant);

        $this->assertStringContainsString('KITCHEN TICKET', $ticket);
        $this->assertStringContainsString('1x Pasta', $ticket);
        $this->assertStringContainsString('No onions', $ticket);
        $this->assertStringContainsString('Serve quickly', $ticket);
        $this->assertStringNotContainsString("\x1B", $ticket);
    }
}