<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponse
{
    protected function success(mixed $data = null, int $status = 200, array $extra = []): JsonResponse
    {
        $payload = ['success' => true, ...$extra];

        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->additional($payload)->response()->setStatusCode($status);
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    protected function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message], $status);
    }
}