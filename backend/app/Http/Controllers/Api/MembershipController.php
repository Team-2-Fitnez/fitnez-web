<?php

namespace App\Http\Controllers\Api;

use App\Features\Payments\Controllers\MemberMembershipController;
use App\Features\Payments\Controllers\MembershipPackageController;
use App\Http\Requests\Common\PublicListRequest;

class MembershipController extends MemberMembershipController
{
    public function packages(PublicListRequest $request)
    {
        return app(MembershipPackageController::class)->index($request);
    }
}
