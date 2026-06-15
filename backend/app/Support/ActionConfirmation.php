<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActionConfirmation
{
    public static function require(Request $request, string $action, string $target): ?JsonResponse
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
}
