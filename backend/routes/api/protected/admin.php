<?php

use App\Features\Notifications\Controllers\Admin\AdminNotificationController;
use App\Features\Reports\Controllers\Admin\AuthActivityReportController;
use App\Features\Reports\Controllers\Admin\LandingVisitReportController;
use App\Features\Reports\Controllers\Admin\MemberPaymentAttendanceReportController;
use App\Features\HireTrainer\Controllers\Admin\ProspectiveMemberReviewController;
use App\Features\Schedule\Controllers\Admin\ScheduleManagementController;
use App\Features\HireTrainer\Controllers\Admin\TrainerApplicationReviewController;
use App\Features\HireTrainer\Controllers\Admin\TrainerManagementController;
use App\Features\Admin\Controllers\UserManagementController;
use App\Features\Booking\Controllers\BookingController;
use App\Features\Excel\Controllers\ExcelExportController;
use App\Features\Payments\Controllers\MemberMembershipController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(EnsureRole::class . ':admin')->group(function () {
    Route::get('/roles', [UserManagementController::class, 'roles']);

    Route::get('/landing-visits', [LandingVisitReportController::class, 'index']);
    Route::get('/landing-visits/summary', [LandingVisitReportController::class, 'summary']);

    Route::get('/auth-activity/summary', [AuthActivityReportController::class, 'summary']);
    Route::get('/auth-activity/logs', [AuthActivityReportController::class, 'logs']);
    Route::get('/auth-activity/registrations', [AuthActivityReportController::class, 'registrations']);

    Route::get('/member-reports/summary', [MemberPaymentAttendanceReportController::class, 'summary']);
    Route::get('/member-reports/payments', [MemberPaymentAttendanceReportController::class, 'payments']);
    Route::get('/member-reports/attendance', [MemberPaymentAttendanceReportController::class, 'attendance']);

    Route::get('/prospective-members', [ProspectiveMemberReviewController::class, 'index']);
    Route::post('/prospective-members/{registration}/approve', [ProspectiveMemberReviewController::class, 'approve']);
    Route::post('/prospective-members/{registration}/reject', [ProspectiveMemberReviewController::class, 'reject']);

    Route::get('/trainer-applications', [TrainerApplicationReviewController::class, 'index']);
    Route::post('/trainer-applications/{application}/approve', [TrainerApplicationReviewController::class, 'approve']);
    Route::post('/trainer-applications/{application}/reject', [TrainerApplicationReviewController::class, 'reject']);
    Route::get('/trainer-applications/{application}/documents/{type}', [TrainerApplicationReviewController::class, 'download'])->whereIn('type', ['cv', 'certificate']);
    Route::get('/trainer-applications/{application}/documents/{type}/stream', [TrainerApplicationReviewController::class, 'stream'])->whereIn('type', ['cv', 'certificate']);

    Route::get('/users/summary', [UserManagementController::class, 'summary']);
    Route::get('/nutrition-monitoring', [MemberPaymentAttendanceReportController::class, 'nutrition']);

    Route::apiResource('users', UserManagementController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('trainers', TrainerManagementController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('schedules', ScheduleManagementController::class)->parameters(['schedules' => 'schedule'])->only(['index', 'store', 'update', 'destroy']);

    Route::get('/bookings/pending-payments', [BookingController::class, 'pendingPayments']);
    Route::post('/bookings/{booking}/confirm-payment', [BookingController::class, 'confirmPayment']);
    Route::post('/bookings/{booking}/reject-payment', [BookingController::class, 'rejectPayment']);
    Route::post('/bookings/auto-complete', [BookingController::class, 'autoCompleteExpiredBookings']);

    Route::get('/payments/pending-renewals', [MemberMembershipController::class, 'pendingRenewals']);
    Route::post('/payments/renewals/{payment}/confirm', [MemberMembershipController::class, 'confirmRenewal']);
    Route::post('/payments/renewals/{payment}/reject', [MemberMembershipController::class, 'rejectRenewal']);

    Route::get('/notifications', [AdminNotificationController::class, 'index']);
    Route::post('/approve/{id}', [AdminNotificationController::class, 'approve']);
    Route::post('/reject/{id}', [AdminNotificationController::class, 'reject']);

    Route::prefix('export')->group(function () {
        Route::get('/landing-visits', [ExcelExportController::class, 'landingVisits']);
        Route::get('/auth-activity', [ExcelExportController::class, 'authActivity']);
        Route::get('/member-reports', [ExcelExportController::class, 'memberReports']);
        Route::get('/member-reports/sse', [ExcelExportController::class, 'memberReportsSse']);
        Route::get('/member-reports/download/{filename}', [ExcelExportController::class, 'memberReportsDownload'])->where('filename', '.*');
        Route::get('/payments', [ExcelExportController::class, 'payments']);
        Route::get('/attendance', [ExcelExportController::class, 'attendance']);
        Route::get('/users', [ExcelExportController::class, 'users']);
        Route::get('/nutrition-monitoring', [ExcelExportController::class, 'nutritionMonitoring']);
    });
});
