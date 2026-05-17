<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    public function successResponse(mixed $data = null, string $message = null, int $statusCode = 200): JsonResponse
    {
        if (!is_null($data)) {
            return response()->json([
                'data' => $data
            ], $statusCode);
        }

        $message = $message ?? __('messages.successful');

        return response()->json([
            'message' => $message
        ], $statusCode);
    }

    public function errorResponse(string $message, string $type = 'logical_exception', int $statusCode = 422): JsonResponse
    {
        return response()->json([
            'type' => $type,
            'message' => $message
        ], $statusCode);
    }
}
