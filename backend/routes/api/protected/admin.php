<?php

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AdminReportController;
use App\Http\Controllers\Api\ProspectiveMemberController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\TrainerController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ExcelController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(EnsureRole::class . ':admin')->group(function () {
    Route::get('/roles', [AdminUserController::class, 'roles']);

    Route::get('/landing-visits', [AdminReportController::class, 'landingVisits']);
    Route::get('/landing-visits/summary', [AdminReportController::class, 'landingVisitSummary']);

    Route::get('/auth-activity/summary', [AdminReportController::class, 'authActivitySummary']);
    Route::get('/auth-activity/logs', [AdminReportController::class, 'authActivityLogs']);
    Route::get('/auth-activity/registrations', [AdminReportController::class, 'authActivityRegistrations']);

    Route::get('/member-reports/summary', [AdminReportController::class, 'memberSummary']);
    Route::get('/member-reports/payments', [AdminReportController::class, 'memberPayments']);
    Route::get('/member-reports/attendance', [AdminReportController::class, 'memberAttendance']);

    Route::get('/prospective-members', [ProspectiveMemberController::class, 'index']);
    Route::post('/prospective-members/{registration}/approve', [ProspectiveMemberController::class, 'approve']);
    Route::post('/prospective-members/{registration}/reject', [ProspectiveMemberController::class, 'reject']);

    Route::get('/trainer-applications', [TrainerController::class, 'applications']);
    Route::post('/trainer-applications/{application}/approve', [TrainerController::class, 'approveApplication']);
    Route::post('/trainer-applications/{application}/reject', [TrainerController::class, 'rejectApplication']);
    Route::get('/trainer-applications/{application}/documents/{type}', [TrainerController::class, 'downloadApplicationDocument'])->whereIn('type', ['cv', 'certificate']);
    Route::get('/trainer-applications/{application}/documents/{type}/stream', [TrainerController::class, 'streamApplicationDocument'])->whereIn('type', ['cv', 'certificate']);

    Route::get('/users/summary', [AdminUserController::class, 'summary']);
    Route::get('/nutrition-monitoring', [AdminReportController::class, 'nutritionMonitoring']);

    Route::apiResource('users', AdminUserController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('trainers', TrainerController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('schedules', ScheduleController::class)->parameters(['schedules' => 'schedule'])->only(['index', 'store', 'update', 'destroy']);

    Route::get('/bookings/pending-payments', [BookingController::class, 'pendingPayments']);
    Route::post('/bookings/{booking}/confirm-payment', [BookingController::class, 'confirmPayment']);
    Route::post('/bookings/{booking}/reject-payment', [BookingController::class, 'rejectPayment']);
    Route::post('/bookings/auto-complete', [BookingController::class, 'autoCompleteExpiredBookings']);

    Route::get('/payments/pending-renewals', [MembershipController::class, 'pendingRenewals']);
    Route::post('/payments/renewals/{payment}/confirm', [MembershipController::class, 'confirmRenewal']);
    Route::post('/payments/renewals/{payment}/reject', [MembershipController::class, 'rejectRenewal']);

    Route::get('/notifications', [NotificationController::class, 'adminIndex']);
    Route::post('/approve/{id}', [NotificationController::class, 'approve']);
    Route::post('/reject/{id}', [NotificationController::class, 'reject']);

    Route::prefix('export')->group(function () {
        Route::get('/landing-visits', [ExcelController::class, 'landingVisits']);
        Route::get('/auth-activity', [ExcelController::class, 'authActivity']);
        Route::get('/member-reports', [ExcelController::class, 'memberReports']);
        Route::get('/member-reports/sse', [ExcelController::class, 'memberReportsSse']);
        Route::get('/member-reports/download/{filename}', [ExcelController::class, 'memberReportsDownload'])->where('filename', '.*');
        Route::get('/payments', [ExcelController::class, 'payments']);
        Route::get('/attendance', [ExcelController::class, 'attendance']);
        Route::get('/users', [ExcelController::class, 'users']);
        Route::get('/nutrition-monitoring', [ExcelController::class, 'nutritionMonitoring']);
    });
});
