<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function __construct(protected ReportsService $reportsService) {}

    public function summary(Request $request)
    {
        $days = (int) $request->query('days', 30);

        return response()->json([
            'success' => true,
            'summary' => $this->reportsService->summary($days),
        ]);
    }

    public function export(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $rows = $this->reportsService->exportRows($days);

        return response()->json([
            'success' => true,
            'rows' => $rows,
            'count' => count($rows),
        ]);
    }
}