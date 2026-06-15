<?php

namespace App\Services\Confirmation;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfirmationService
{
    public function require(Request $request, string $action, string $target): ?JsonResponse
    {
        if ($request->boolean('confirmed')) {
            return null;
        }

        return ApiResponse::error(
            "Confirmation required before {$action}.",
            [
                'confirmation_required' => true,
                'action' => $action,
                'target' => $target,
                'expected_payload' => ['confirmed' => true],
            ],
            409
        );
    }

    public static function check(Request $request, string $action, string $target): ?JsonResponse
    {
        return app(self::class)->require($request, $action, $target);
    }
}
