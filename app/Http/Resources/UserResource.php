<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->getRoleNames()->first(), // Spatie stores roles in a pivot table; flatten to the single role string the frontend expects
            'restaurant_id' => $this->restaurant_id,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
        ];
        // password is never listed here — this is the resource
        // pattern's version of Node's toJSON() stripping it: the
        // field simply doesn't exist in the output unless explicitly
        // added, rather than needing an explicit deletion step.
    }
}