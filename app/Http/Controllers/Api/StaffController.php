<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\ToggleStaffStatusRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\StaffService;
use App\Traits\ApiResponse;

class StaffController extends Controller
{
    use ApiResponse;

    public function __construct(protected StaffService $staffService) {}

    public function index()
    {
        $staff = $this->staffService->list();

        return $this->success(UserResource::collection($staff), extra: ['count' => $staff->count()]);
    }

    public function store(StoreStaffRequest $request)
    {
        $staff = $this->staffService->create($request->validated());

        return $this->success(new UserResource($staff), 201);
    }

    public function update(UpdateStaffRequest $request, User $staff)
    {
        $staff = $this->staffService->update($staff, $request->validated(), $request);

        return $this->success(new UserResource($staff));
    }

    public function toggleStatus(ToggleStaffStatusRequest $request, User $staff)
    {
        $staff = $this->staffService->toggleStatus($staff, $request->boolean('is_active'));

        return $this->success(new UserResource($staff));
    }

    public function destroy(User $staff)
    {
        $this->staffService->delete($staff);

        return $this->message('Staff account deleted');
    }
}