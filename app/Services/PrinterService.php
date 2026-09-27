<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Printer;
use App\Models\Restaurant;
use App\Support\Tenant;

class PrinterService
{
    public function __construct(protected EscPosService $escPos) {}

    public function list()
    {
        return Printer::orderBy('created_at')->get();
    }

    public function create(array $data): Printer
    {
        $data = $this->normalizeConnectionSettings($data);

        return Printer::create($data);
    }

    public function update(Printer $printer, array $data): Printer
    {
        $data = $this->normalizeConnectionSettings($data, $printer);
        $printer->update($data);

        return $printer;
    }

    public function delete(Printer $printer): void
    {
        $printer->delete();
    }

    public function buildPrintJob(int $printerId, int $orderId, string $jobType): array
    {
        $printer = Printer::where('is_active', true)->findOrFail($printerId);
        if (! in_array($printer->purpose, ['both', $jobType], true)) {
            $jobName = $jobType === 'kitchen' ? 'kitchen orders' : 'receipts';
            throw ApiException::badRequest("This printer is not set up to print {$jobName}.");
        }

        $order = Order::with(['items.modifiers', 'table:id,table_number'])->findOrFail($orderId);
        $restaurant = Restaurant::allRestaurants()->find(Tenant::id());

        $bytes = $jobType === 'kitchen'
            ? $this->escPos->buildKitchenTicket($order, $restaurant)
            : $this->escPos->buildReceipt($order, $restaurant);

        return [
            'connection_type' => $printer->connection_type,
            'ip' => $printer->ip,
            'port' => $printer->port,
            'system_printer_name' => $printer->system_printer_name,
            'data' => base64_encode($bytes),
        ];
    }

    protected function normalizeConnectionSettings(array $data, ?Printer $printer = null): array
    {
        $connectionType = $data['connection_type'] ?? $printer?->connection_type;
        if ($connectionType === 'network') {
            $data['system_printer_name'] = null;
            $data['port'] = $data['port'] ?? $printer?->port ?? 9100;
        } elseif ($connectionType === 'system') {
            $data['ip'] = null;
            $data['port'] = $printer?->port ?? 9100;
        }

        return $data;
    }
}