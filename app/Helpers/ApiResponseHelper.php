<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiResponseHelper
{
    /**
     * Success response
     */
    public static function success(string $message, array $data = [], int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Error response
     */
    public static function error(string $message, array $errors = [], int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Info response
     */
    public static function info(string $message, array $data = [], int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'status' => 'info',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Not found response
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, [], Response::HTTP_NOT_FOUND);
    }

    /**
     * Validation error response
     */
    public static function validationError(string $message = 'Validation failed', array $errors = []): JsonResponse
    {
        return self::error($message, $errors, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
