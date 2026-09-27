<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Table\StoreTableRequest;
use App\Http\Requests\Table\UpdateTableRequest;
use App\Http\Resources\TableResource;
use App\Models\DiningTable;
use App\Services\QrCodeService;
use App\Services\TableService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TableController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TableService $tableService,
        protected QrCodeService $qrCodeService,
    ) {}

    public function index(Request $request)
    {
        $tables = $this->tableService->list(
            status: $request->query('status'),
            branchId: $request->query('branch_id'),
        );

        return $this->success(TableResource::collection($tables), extra: ['count' => $tables->count()]);
    }

    public function store(StoreTableRequest $request)
    {
        $table = $this->tableService->create($request->validated());

        return $this->success(new TableResource($table), 201);
    }

    public function update(UpdateTableRequest $request, DiningTable $table)
    {
        return $this->success(new TableResource($this->tableService->update($table, $request->validated())));
    }

    public function updateStatus(Request $request, DiningTable $table)
    {
        $request->validate(['status' => ['required', 'in:available,occupied,reserved']]);

        return $this->success(new TableResource($this->tableService->updateStatus($table, $request->status)));
    }

    public function destroy(DiningTable $table)
    {
        $this->tableService->delete($table);

        return $this->message('Table deleted');
    }

    public function qrCode(DiningTable $table)
    {
        return $this->success($this->qrCodeService->getImageForTable($table));
    }

    public function regenerateQr(DiningTable $table)
    {
        $table = $this->tableService->regenerateQrToken($table);

        return $this->success(new TableResource($table));
    }
}