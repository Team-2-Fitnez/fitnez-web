<?php

namespace App\Support;

use App\Services\Confirmation\ConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActionConfirmation
{
    public static function require(Request $request, string $action, string $target): ?JsonResponse
    {
        return ConfirmationService::check($request, $action, $target);
    }
}
