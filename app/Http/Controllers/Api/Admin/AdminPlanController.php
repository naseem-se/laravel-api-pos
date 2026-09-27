<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Services\PlanManagementService;

class AdminPlanController extends Controller
{
    public function __construct(protected PlanManagementService $planService) {}

    public function index()
    {
        return response()->json(['success' => true, 'plans' => PlanResource::collection($this->planService->listAll())]);
    }

    public function store(StorePlanRequest $request)
    {
        $plan = $this->planService->create($request->validated());

        return response()->json(['success' => true, 'plan' => new PlanResource($plan)], 201);
    }

    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $plan = $this->planService->update($plan, $request->validated());

        return response()->json(['success' => true, 'plan' => new PlanResource($plan)]);
    }

    public function destroy(Plan $plan)
    {
        $this->planService->delete($plan);

        return response()->json(['success' => true, 'message' => 'Plan deleted']);
    }
}