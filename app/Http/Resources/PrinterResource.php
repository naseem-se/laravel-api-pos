<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrinterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'purpose' => $this->purpose,
            'connection_type' => $this->connection_type,
            'ip' => $this->ip,
            'port' => $this->port,
            'system_printer_name' => $this->system_printer_name,
            'is_active' => $this->is_active,
        ];
    }
}