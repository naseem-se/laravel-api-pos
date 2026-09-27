<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;

// Builds raw ESC/POS byte sequences by hand. There is no widely-
// maintained PHP equivalent of node-thermal-printer with a clean
// high-level API, so this constructs the actual escape codes — a
// small, fixed set of commands covers everything a receipt/kitchen
// ticket needs: bold, center/left align, cut, and plain text lines.
// Xprinter and other generic ESC/POS clones all honor this same
// command set (it's the industry-standard subset, not Epson-proprietary).
class EscPosService
{
    protected const ESC = "\x1B";
    protected const GS = "\x1D";

    protected string $buffer = '';

    public function reset(): static
    {
        $this->buffer = self::ESC.'@'; // initialize printer
        return $this;
    }

    public function alignCenter(): static
    {
        $this->buffer .= self::ESC.'a'."\x01";
        return $this;
    }

    public function alignLeft(): static
    {
        $this->buffer .= self::ESC.'a'."\x00";
        return $this;
    }

    public function bold(bool $on): static
    {
        $this->buffer .= self::ESC.'E'.($on ? "\x01" : "\x00");
        return $this;
    }

    public function println(string $text = ''): static
    {
        $this->buffer .= $text."\n";
        return $this;
    }

    public function drawLine(int $width = 32): static
    {
        $this->buffer .= str_repeat('-', $width)."\n";
        return $this;
    }

    // Two-column line: label left-padded, value right-aligned within
    // a fixed character width — the plain-text equivalent of the
    // Node library's tableCustom() helper, adjusted for the paper
    // width passed into build().
    public function row(string $left, string $right, int $width): static
    {
        $spaces = max(1, $width - mb_strlen($left) - mb_strlen($right));
        $this->buffer .= $left.str_repeat(' ', $spaces).$right."\n";
        return $this;
    }

    public function cut(): static
    {
        $this->buffer .= "\n\n\n".self::GS.'V'."\x00"; // feed + full cut
        return $this;
    }

    public function getBuffer(): string
    {
        return $this->buffer;
    }

    protected function charWidth(?string $paperWidth): int
    {
        return $paperWidth === '58mm' ? 32 : 48;
    }

    protected function money(float $amount): string
    {
        return 'Rs '.number_format($amount, 0);
    }

    public function buildReceipt(Order $order, Restaurant $restaurant): string
    {
        $width = $this->charWidth($restaurant->settings['receiptPaperWidth'] ?? '80mm');
        $this->reset();

        $this->alignCenter()->bold(true)->println($restaurant->name)->bold(false);
        if ($restaurant->address) $this->println($restaurant->address);
        if ($restaurant->contact_phone) $this->println($restaurant->contact_phone);
        $this->drawLine($width);

        $this->alignLeft();
        $this->println("Order #{$order->order_number}");
        $this->println($order->created_at->format('Y-m-d H:i'));
        $tableSuffix = $order->table ? " - Table {$order->table->table_number}" : '';
        $this->println($order->order_type.$tableSuffix);
        $this->drawLine($width);

        foreach ($order->items as $item) {
            $this->row("{$item->quantity}x {$item->name}", $this->money((float) $item->subtotal), $width);
            if ($item->modifiers->isNotEmpty()) {
                $this->println('  '.$item->modifiers->pluck('option_name')->join(', '));
            }
        }

        $this->drawLine($width);
        $this->row('Subtotal', $this->money((float) $order->subtotal_amount), $width);
        $this->row('Tax', $this->money((float) $order->tax_amount), $width);
        if ((float) $order->discount_amount > 0) {
            $this->row('Discount', '-'.$this->money((float) $order->discount_amount), $width);
        }
        $this->bold(true)->row('TOTAL', $this->money((float) $order->total_amount), $width)->bold(false);
        $this->drawLine($width);

        $this->alignCenter()->println('Thank you!');
        $this->cut();

        return $this->getBuffer();
    }

    public function buildKitchenTicket(Order $order, Restaurant $restaurant): string
    {
        $width = $this->charWidth($restaurant->settings['receiptPaperWidth'] ?? '80mm');
        $this->reset();

        $this->alignCenter()->bold(true)->println('KITCHEN TICKET')->bold(false);
        $this->drawLine($width);

        $this->alignLeft()->bold(true)->println("Order #{$order->order_number}")->bold(false);
        $tableSuffix = $order->table ? " - TABLE {$order->table->table_number}" : '';
        $this->println($order->order_type.$tableSuffix);
        if ($order->customer_name) $this->println("For: {$order->customer_name}");
        $this->println($order->created_at->format('Y-m-d H:i'));
        $this->drawLine($width);

        foreach ($order->items as $item) {
            $this->bold(true)->println("{$item->quantity}x {$item->name}")->bold(false);
            if ($item->modifiers->isNotEmpty()) {
                $this->println('  - '.$item->modifiers->pluck('option_name')->join(', '));
            }
            if ($item->notes) $this->println("  Note: {$item->notes}");
        }

        if ($order->notes) {
            $this->drawLine($width);
            $this->println("Order notes: {$order->notes}");
        }

        $this->cut();

        return $this->getBuffer();
    }
}