<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = $this->image_url;
        if ($imageUrl) {
            $imagePath = parse_url($imageUrl, PHP_URL_PATH) ?: $imageUrl;
            $imageHost = parse_url($imageUrl, PHP_URL_HOST);
            $isCurrentHost = ! $imageHost || $imageHost === $request->getHost();
            $isLocalHost = in_array($imageHost, ['localhost', '127.0.0.1'], true);

            if (str_starts_with($imagePath, '/storage/') && ($isCurrentHost || $isLocalHost)) {
                $imageUrl = $request->getSchemeAndHttpHost().$imagePath;
            } elseif (! $imageHost && ! parse_url($imageUrl, PHP_URL_SCHEME)) {
                $imagePath = preg_replace('#^/?storage/#', '', $imagePath);
                $imageUrl = $request->getSchemeAndHttpHost().'/storage/'.ltrim($imagePath, '/');
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'image_url' => $imageUrl,
            'is_available' => $this->is_available,
            'preparation_time_minutes' => $this->preparation_time_minutes,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'modifier_groups' => $this->whenLoaded('modifierGroups', fn () => $this->modifierGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'selection_type' => $group->selection_type,
                'pricing_mode' => $group->pricing_mode,
                'is_required' => $group->is_required,
                'options' => $group->options->map(fn ($opt) => [
                    'id' => $opt->id,
                    'name' => $opt->name,
                    'price_delta' => (float) $opt->price_delta,
                ]),
            ])),
        ];
    }
}