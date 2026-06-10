<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponses
{
    public static function unauthorized(string $message = 'Unauthorized', mixed $data = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 401);
    }

    public static function created(string $message = 'Created', mixed $data = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 201);
    }

    public static function ok(string $message = 'Success', mixed $data = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 200);
    }
}
