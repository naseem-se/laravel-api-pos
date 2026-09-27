<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'order_type' => $this->order_type,
            'status' => $this->status,
            'subtotal_amount' => (float) $this->subtotal_amount,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'notes' => $this->notes,
            'customer_name' => $this->customer_name,
            'created_at' => $this->created_at,

            'table' => $this->whenLoaded('table', fn () => $this->table ? [
                'id' => $this->table->id, 'table_number' => $this->table->table_number,
            ] : null),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id, 'name' => $this->branch->name,
            ] : null),
            'placed_by' => $this->whenLoaded('placedBy', fn () => $this->placedBy ? [
                'id' => $this->placedBy->id, 'name' => $this->placedBy->name,
            ] : null),

            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    $modifiers = $item->relationLoaded('modifiers') && $item->modifiers
                        ? $item->modifiers->map(fn ($m) => [
                            'group_name' => $m->group_name,
                            'option_name' => $m->option_name,
                            'price_delta' => (float) $m->price_delta,
                            'pricing_mode' => $m->pricing_mode,
                        ])->all()
                        : [];
                    
                    return [
                        'id' => $item->id,
                        'menu_item_id' => $item->menu_item_id,
                        'name' => $item->name,
                        'price' => (float) $item->price,
                        'quantity' => $item->quantity,
                        'subtotal' => (float) $item->subtotal,
                        'notes' => $item->notes,
                        'selected_modifiers' => $modifiers,
                    ];
                });
            }),

            'status_history' => $this->whenLoaded('statusHistory', fn () => $this->statusHistory->map(fn ($h) => [
                'status' => $h->status,
                'changed_at' => $h->changed_at,
            ])),
        ];
    }
}