<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequiresConfirmation
{
    public static function check(Request $request, string $action, string $target): ?JsonResponse
    {
        if ($request->boolean('confirmed')) {
            return null;
        }

        return ApiResponse::error('This action requires user confirmation.', [
            'confirmation_required' => true,
            'action' => $action,
            'target' => $target,
        ], 409);
    }
}
