<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Printer\StorePrinterRequest;
use App\Http\Requests\Printer\UpdatePrinterRequest;
use App\Http\Resources\PrinterResource;
use App\Models\Printer;
use App\Services\PrinterService;
use Illuminate\Http\Request;

class PrinterController extends Controller
{
    public function __construct(protected PrinterService $printerService) {}

    public function index()
    {
        return response()->json(['success' => true, 'printers' => PrinterResource::collection($this->printerService->list())]);
    }

    public function store(StorePrinterRequest $request)
    {
        $printer = $this->printerService->create($request->validated());

        return response()->json(['success' => true, 'printer' => new PrinterResource($printer)], 201);
    }

    public function update(UpdatePrinterRequest $request, Printer $printer)
    {
        $printer = $this->printerService->update($printer, $request->validated());

        return response()->json(['success' => true, 'printer' => new PrinterResource($printer)]);
    }

    public function destroy(Printer $printer)
    {
        $this->printerService->delete($printer);

        return response()->json(['success' => true, 'message' => 'Printer deleted']);
    }

    public function printJob(Request $request, Printer $printer)
    {
        $request->validate([
            'order_id' => ['required', 'integer'],
            'type' => ['required', 'in:receipt,kitchen'],
        ]);

        $job = $this->printerService->buildPrintJob($printer->id, (int) $request->query('order_id'), $request->query('type'));

        return response()->json(['success' => true, ...$job]);
    }
}