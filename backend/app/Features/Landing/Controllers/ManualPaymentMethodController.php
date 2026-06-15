<?php

namespace App\Features\Landing\Controllers;

use App\Http\Controllers\Controller;

use App\Shared\Requests\Common\PublicListRequest;
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
