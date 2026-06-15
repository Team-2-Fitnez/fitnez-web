<?php

namespace App\Http\Controllers\Api;

use App\Features\MemberProgressMonitoring\Controllers\Trainer\MemberFitnessMonitoringController;
use App\Features\MemberProgressMonitoring\Requests\Trainer\TrainerMonitoringRequest;
use App\Features\RentHistory\Controllers\Trainer\IncomingRentHistoryController;
use App\Features\RentHistory\Requests\Trainer\IncomingRentHistoryRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class TrainerWorkspaceController extends Controller
{
    public function monitoringSummary(Request $request)
    {
        return app(MemberFitnessMonitoringController::class)->summary($request);
    }

    public function monitoringMembers(TrainerMonitoringRequest $request)
    {
        return app(MemberFitnessMonitoringController::class)->members($request);
    }

    public function monitoringMember(Request $request, User $member)
    {
        return app(MemberFitnessMonitoringController::class)->show($request, $member);
    }

    public function rentSummary(Request $request)
    {
        return app(IncomingRentHistoryController::class)->summary($request);
    }

    public function rentBreakdown(Request $request)
    {
        return app(IncomingRentHistoryController::class)->breakdown($request);
    }

    public function rentHistory(IncomingRentHistoryRequest $request)
    {
        return app(IncomingRentHistoryController::class)->index($request);
    }
}
