<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function stats()
    {
        return response()->json(['success' => true, 'stats' => $this->dashboardService->stats()]);
    }
}