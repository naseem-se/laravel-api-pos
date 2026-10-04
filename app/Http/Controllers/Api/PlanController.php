<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Traits\ApiResponse;

class PlanController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $plans = Plan::where('is_active', true)->orderBy('display_order')->orderBy('id')->get();

        return $this->success(PlanResource::collection($plans));
    }
}