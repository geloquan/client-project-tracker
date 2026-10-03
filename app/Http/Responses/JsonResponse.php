<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse as LaravelJsonResponse;

class JsonResponse
{
    public static function success(mixed $data, string $message = 'Success', int $status = 200): LaravelJsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error(string $message, int $status, array $errors = []): LaravelJsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
