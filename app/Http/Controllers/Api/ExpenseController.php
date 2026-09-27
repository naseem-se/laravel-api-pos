<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StoreExpenseRequest;
use App\Http\Requests\Purchasing\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ApiResponse;

    public function __construct(protected ExpenseService $expenseService) {}

    public function index(Request $request)
    {
        return $this->success($this->expenseService->list([
            'search' => $request->query('search'),
            'category' => $request->query('category'),
            'branch_id' => $request->query('branch_id'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'include_voided' => $request->boolean('include_voided'),
        ]));
    }

    public function summary(Request $request)
    {
        return $this->success($this->expenseService->summary($request->only(['from', 'to', 'branch_id'])));
    }

    public function store(StoreExpenseRequest $request)
    {
        return $this->success($this->expenseService->create($request->validated(), $request->user()), 201);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        return $this->success($this->expenseService->update($expense, $request->validated()));
    }

    public function destroy(Expense $expense, Request $request)
    {
        return $this->success($this->expenseService->void($expense, $request->user()));
    }
}