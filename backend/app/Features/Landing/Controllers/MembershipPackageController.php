<?php

namespace App\Features\Landing\Controllers;

use App\Http\Controllers\Controller;

use App\Shared\Requests\Common\PublicListRequest;
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
