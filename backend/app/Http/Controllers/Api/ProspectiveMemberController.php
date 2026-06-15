<?php

namespace App\Http\Controllers\Api;

use App\Features\Auth\Actions\Auth\CreateMemberFromApprovedRegistrationAction;
use App\Features\HireTrainer\Controllers\Admin\ProspectiveMemberReviewController;
use App\Features\HireTrainer\Requests\Admin\RejectProspectiveMemberRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTableRequest;
use App\Models\ProspectiveMemberRegistration;

class ProspectiveMemberController extends Controller
{
    public function index(IndexTableRequest $request)
    {
        return app(ProspectiveMemberReviewController::class)->index($request);
    }

    public function approve(ProspectiveMemberRegistration $registration, CreateMemberFromApprovedRegistrationAction $action)
    {
        return app(ProspectiveMemberReviewController::class)->approve($registration, $action);
    }

    public function reject(RejectProspectiveMemberRequest $request, ProspectiveMemberRegistration $registration)
    {
        return app(ProspectiveMemberReviewController::class)->reject($request, $registration);
    }
}
