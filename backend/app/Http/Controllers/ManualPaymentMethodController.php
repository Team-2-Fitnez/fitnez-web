<?php

namespace App\Http\Controllers;

use App\Http\Requests\Common\PublicListRequest;
use App\Models\ManualPaymentMethod;
use App\Support\ApiResponse;

class ManualPaymentMethodController extends Controller
{
    public function index(PublicListRequest $request)
    {
        $methods = ManualPaymentMethod::query()
            ->active()
            ->orderBy('id')
            ->limit($request->limit(20))
            ->get();

        return ApiResponse::success('Manual payment methods loaded.', $methods);
    }
}
