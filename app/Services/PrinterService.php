<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Printer;
use App\Models\Restaurant;
use App\Support\Tenant;

class PrinterService
{
    public function __construct(
        protected EscPosService $escPos,
        protected DriverTextPrintService $driverText,
    ) {}

    public function list()
    {
        return Printer::orderBy('created_at')->get();
    }

    public function create(array $data): Printer
    {
        $data = $this->normalizeConnectionSettings($data);
        $this->assertCompatibleOutputMode($data['connection_type'], $data['output_mode']);

        return Printer::create($data);
    }

    public function update(Printer $printer, array $data): Printer
    {
        $data = $this->normalizeConnectionSettings($data, $printer);
        $this->assertCompatibleOutputMode(
            $data['connection_type'] ?? $printer->connection_type,
            $data['output_mode'] ?? $printer->output_mode ?? 'escpos'
        );
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
        if ($jobType === 'receipt' && $order->payment_method === null) {
            throw ApiException::badRequest('Record payment before printing the receipt.');
        }
        $restaurant = Restaurant::allRestaurants()->find(Tenant::id());

        $outputMode = $printer->output_mode ?? 'escpos';
        $formatter = $outputMode === 'driver_text' ? $this->driverText : $this->escPos;
        $content = $jobType === 'kitchen'
            ? $formatter->buildKitchenTicket($order, $restaurant)
            : $formatter->buildReceipt($order, $restaurant);

        return [
            'connection_type' => $printer->connection_type,
            'output_mode' => $outputMode,
            'ip' => $printer->ip,
            'port' => $printer->port,
            'system_printer_name' => $printer->system_printer_name,
            'data' => base64_encode($content),
        ];
    }

    protected function assertCompatibleOutputMode(string $connectionType, string $outputMode): void
    {
        if ($outputMode === 'driver_text' && $connectionType !== 'system') {
            throw ApiException::badRequest('Installed-driver output requires an OS-installed printer.');
        }
    }

    protected function normalizeConnectionSettings(array $data, ?Printer $printer = null): array
    {
        $connectionType = $data['connection_type'] ?? $printer?->connection_type;
        $data['output_mode'] = $data['output_mode'] ?? $printer?->output_mode ?? 'escpos';
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