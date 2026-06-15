<?php

namespace App\Http\Controllers\Api;

use App\Features\PaymentsReview\Controllers\Admin\MemberPaymentAttendanceReportController;
use App\Features\PaymentsReview\Requests\Admin\MemberPaymentAttendanceReportRequest;
use App\Features\Reports\Controllers\Admin\AuthActivityReportController;
use App\Features\Reports\Controllers\Admin\LandingVisitReportController;
use App\Features\Reports\Controllers\Analytics\LandingVisitController;
use App\Features\Reports\Requests\Admin\AuthActivityReportRequest;
use App\Features\Reports\Requests\Analytics\StoreLandingVisitRequest;
use App\Services\Analytics\LandingVisitTracker;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTableRequest;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function storeLandingVisit(StoreLandingVisitRequest $request, LandingVisitTracker $tracker)
    {
        return app(LandingVisitController::class)->store($request, $tracker);
    }

    public function heartbeatLandingVisit(StoreLandingVisitRequest $request, LandingVisitTracker $tracker)
    {
        return app(LandingVisitController::class)->heartbeat($request, $tracker);
    }

    public function landingVisits(IndexTableRequest $request)
    {
        return app(LandingVisitReportController::class)->index($request);
    }

    public function landingVisitSummary()
    {
        return app(LandingVisitReportController::class)->summary();
    }

    public function authActivitySummary(Request $request)
    {
        return app(AuthActivityReportController::class)->summary($request);
    }

    public function authActivityLogs(AuthActivityReportRequest $request)
    {
        return app(AuthActivityReportController::class)->logs($request);
    }

    public function authActivityRegistrations(AuthActivityReportRequest $request)
    {
        return app(AuthActivityReportController::class)->registrations($request);
    }

    public function memberSummary(Request $request)
    {
        return app(MemberPaymentAttendanceReportController::class)->summary($request);
    }

    public function memberPayments(MemberPaymentAttendanceReportRequest $request)
    {
        return app(MemberPaymentAttendanceReportController::class)->payments($request);
    }

    public function memberAttendance(MemberPaymentAttendanceReportRequest $request)
    {
        return app(MemberPaymentAttendanceReportController::class)->attendance($request);
    }

    public function nutritionMonitoring(Request $request)
    {
        return app(MemberPaymentAttendanceReportController::class)->nutrition($request);
    }
}
