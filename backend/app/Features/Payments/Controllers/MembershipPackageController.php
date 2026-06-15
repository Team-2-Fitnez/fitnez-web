<?php

namespace App\Features\Payments\Controllers;

use App\Http\Controllers\Controller;

use App\Http\Requests\Common\PublicListRequest;
use App\Models\MembershipPackage;
use App\Support\ApiResponse;

class MembershipPackageController extends Controller
{
    public function index(PublicListRequest $request)
    {
        $packages = MembershipPackage::query()
            ->active()
            ->orderBy('duration_months')
            ->limit($request->limit(20))
            ->get();

        return ApiResponse::success('Membership packages loaded.', $packages);
    }
}
