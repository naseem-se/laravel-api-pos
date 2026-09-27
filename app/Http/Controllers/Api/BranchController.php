<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Services\BranchService;
use App\Traits\ApiResponse;

class BranchController extends Controller
{
    use ApiResponse;

    public function __construct(protected BranchService $branchService) {}

    public function index()
    {
        return $this->success(BranchResource::collection($this->branchService->list()));
    }

    public function store(StoreBranchRequest $request)
    {
        $branch = $this->branchService->create($request->validated());

        return $this->success(new BranchResource($branch), 201);
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        return $this->success(new BranchResource($this->branchService->update($branch, $request->validated())));
    }

    public function destroy(Branch $branch)
    {
        $this->branchService->delete($branch);

        return $this->message('Branch deleted');
    }
}