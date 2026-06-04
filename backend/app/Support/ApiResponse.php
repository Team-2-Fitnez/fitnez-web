<?php
namespace App\Support;
use Illuminate\Http\JsonResponse;
class ApiResponse {
    public static function success(string $message, mixed $data = null, int $status = 200): JsonResponse { return ApiResponseBuilder::make()->success(true)->message($message)->data($data)->status($status)->build(); }
    public static function error(string $message, array $errors = [], int $status = 400): JsonResponse { return ApiResponseBuilder::make()->success(false)->message($message)->errors($errors)->status($status)->build(); }
}
