<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;

class DriverTextPrintService
{
    public function buildReceipt(Order $order, Restaurant $restaurant): string
    {
        $width = $this->characterWidth($restaurant);
        $lines = [
            ...$this->wrap($restaurant->name, $width),
        ];

        if ($restaurant->address) $lines = [...$lines, ...$this->wrap($restaurant->address, $width)];
        if ($restaurant->contact_phone) $lines[] = $restaurant->contact_phone;

        $lines[] = str_repeat('-', $width);
        $lines[] = "Order #{$order->order_number}";
        $lines[] = $order->created_at->format('Y-m-d H:i');
        $tableSuffix = $order->table ? " - Table {$order->table->table_number}" : '';
        $lines = [...$lines, ...$this->wrap($order->order_type.$tableSuffix, $width)];
        $lines[] = str_repeat('-', $width);

        foreach ($order->items as $item) {
            $lines = [...$lines, ...$this->wrap("{$item->quantity}x {$item->name}", $width)];
            $lines[] = $this->amountLine('', $this->money((float) $item->subtotal), $width);
            if ($item->modifiers->isNotEmpty()) {
                $lines = [...$lines, ...$this->wrap('  '.$item->modifiers->pluck('option_name')->join(', '), $width)];
            }
            if ($item->notes) $lines = [...$lines, ...$this->wrap('  Note: '.$item->notes, $width)];
        }

        $lines[] = str_repeat('-', $width);
        $lines[] = $this->amountLine('Subtotal', $this->money((float) $order->subtotal_amount), $width);
        $lines[] = $this->amountLine('Tax', $this->money((float) $order->tax_amount), $width);
        if ((float) $order->discount_amount > 0) {
            $lines[] = $this->amountLine('Discount', '-'.$this->money((float) $order->discount_amount), $width);
        }
        $lines[] = $this->amountLine('TOTAL', $this->money((float) $order->total_amount), $width);
        if ($order->payment_method) $lines[] = $this->amountLine('Paid by', ucfirst($order->payment_method), $width);
        $lines[] = str_repeat('-', $width);
        $lines[] = 'Thank you!';

        return implode("\n", $lines)."\n\n";
    }

    public function buildKitchenTicket(Order $order, Restaurant $restaurant): string
    {
        $width = $this->characterWidth($restaurant);
        $tableSuffix = $order->table ? " - TABLE {$order->table->table_number}" : '';
        $lines = [
            'KITCHEN TICKET',
            str_repeat('-', $width),
            "Order #{$order->order_number}",
        ];
        $lines = [...$lines, ...$this->wrap($order->order_type.$tableSuffix, $width)];

        if ($order->customer_name) $lines = [...$lines, ...$this->wrap("For: {$order->customer_name}", $width)];
        $lines[] = $order->created_at->format('Y-m-d H:i');
        $lines[] = str_repeat('-', $width);

        foreach ($order->items as $item) {
            $lines = [...$lines, ...$this->wrap("{$item->quantity}x {$item->name}", $width)];
            if ($item->modifiers->isNotEmpty()) {
                $lines = [...$lines, ...$this->wrap('  - '.$item->modifiers->pluck('option_name')->join(', '), $width)];
            }
            if ($item->notes) $lines = [...$lines, ...$this->wrap('  Note: '.$item->notes, $width)];
        }

        if ($order->notes) {
            $lines[] = str_repeat('-', $width);
            $lines = [...$lines, ...$this->wrap('Order notes: '.$order->notes, $width)];
        }

        return implode("\n", $lines)."\n\n";
    }

    private function characterWidth(Restaurant $restaurant): int
    {
        return ($restaurant->settings['receiptPaperWidth'] ?? '80mm') === '58mm' ? 32 : 48;
    }

    private function amountLine(string $label, string $amount, int $width): string
    {
        $labelWidth = max(0, $width - mb_strwidth($amount) - 1);
        $label = mb_strimwidth($label, 0, $labelWidth, '');

        return $label.str_repeat(' ', max(1, $width - mb_strwidth($label) - mb_strwidth($amount))).$amount;
    }

    private function wrap(string $text, int $width): array
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
        $lines = [];
        $line = '';

        foreach (preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            if ($line !== '' && mb_strwidth($line.' '.$word) > $width) {
                $lines[] = $line;
                $line = '';
            }

            if (mb_strwidth($word) > $width) {
                foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $character) {
                    if ($line !== '' && mb_strwidth($line.$character) > $width) {
                        $lines[] = $line;
                        $line = '';
                    }
                    $line .= $character;
                }
                continue;
            }

            $line = $line === '' ? $word : $line.' '.$word;
        }

        if ($line !== '') $lines[] = $line;

        return $lines ?: [''];
    }

    private function money(float $amount): string
    {
        return 'Rs '.number_format($amount, 0);
    }
}