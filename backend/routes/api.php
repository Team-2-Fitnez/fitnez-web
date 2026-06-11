<?php
use App\Http\Controllers\Admin\AuthActivityReportController;
use App\Http\Controllers\SseController;
use App\Http\Controllers\Admin\MemberPaymentAttendanceReportController;
use App\Http\Controllers\Trainer\IncomingRentHistoryController;
use App\Http\Controllers\Trainer\MemberFitnessMonitoringController;
use App\Http\Middleware\EnsureTrainerWorkspaceAccess;
use App\Http\Controllers\Admin\LandingVisitReportController;
use App\Http\Controllers\Admin\ProspectiveMemberReviewController;
use App\Http\Controllers\Admin\ScheduleManagementController;
use App\Http\Controllers\Admin\TrainerManagementController;
use App\Http\Controllers\Admin\TrainerApplicationReviewController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Analytics\LandingVisitController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BrowserTrackingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\ExcelExportController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ManualPaymentMethodController;
use App\Http\Controllers\ManualProspectiveRegistrationController;
use App\Http\Controllers\MembershipPackageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TrainerApplicationController;
use App\Http\Controllers\WorkoutPlanController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\MemberPaymentController;
use App\Http\Controllers\MemberClassesController;
use App\Http\Controllers\MemberMembershipController;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureActiveMembership;
use App\Http\Middleware\JwtAuthenticate;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CookieConsentController;

Route::post('/cookie-consents', [CookieConsentController::class, 'store']);
Route::get('/landing',[LandingController::class,'index']);
Route::get('/faqs', [FaqController::class, 'index']);
Route::get('/faqs/categories', [FaqController::class, 'categories']);
Route::get('/membership-packages',[MembershipPackageController::class,'index']);
Route::get('/manual-payment-methods',[ManualPaymentMethodController::class,'index']);
Route::prefix('analytics')->group(function(){ Route::post('/landing-visit',[LandingVisitController::class,'store']); Route::post('/landing-visit/heartbeat',[LandingVisitController::class,'heartbeat']); });
Route::prefix('auth')->group(function(){
    Route::post('/prospective-registration/start',[ManualProspectiveRegistrationController::class,'start']);
    Route::post('/prospective-registration/upload-proof',[ManualProspectiveRegistrationController::class,'uploadProof']);
    Route::get('/prospective-registration/status',[ManualProspectiveRegistrationController::class,'status']);
    Route::post('/register-prospective-member',[AuthController::class,'registerProspectiveMember']);
    Route::post('/request-login-otp',[AuthController::class,'requestLoginOtp']); Route::post('/verify-login-otp',[AuthController::class,'verifyLoginOtp']); Route::post('/password/forgot',[AuthController::class,'forgotPassword']); Route::post('/password/reset',[AuthController::class,'resetPassword']); Route::post('/login',[AuthController::class,'login']); Route::post('/member-login',[AuthController::class,'memberLogin']);
    Route::post('/otp/send',[OtpController::class,'send']); Route::post('/otp/verify',[OtpController::class,'verify']);
    Route::middleware(JwtAuthenticate::class)->group(function(){ Route::get('/me',[AuthController::class,'me']); Route::patch('/profile',[AuthController::class,'updateProfile']); Route::post('/logout',[AuthController::class,'logout']); });
});

// Public trainer list (for member hire-trainer page)
Route::get('/trainers/list', [App\Http\Controllers\Admin\TrainerManagementController::class, 'publicList']);

Route::middleware(JwtAuthenticate::class)->group(function(){
    Route::prefix('browser')->group(function(){ Route::post('/heartbeat',[BrowserTrackingController::class,'heartbeat']); Route::post('/elect-leader',[BrowserTrackingController::class,'electLeader']); Route::post('/release-leader',[BrowserTrackingController::class,'releaseLeader']); });
    Route::get('/dashboard/summary',[DashboardController::class,'summary']); Route::get('/dashboard/stream',[DashboardController::class,'stream']);

    // Notifications — markAllRead BEFORE {notification}/read to avoid route collision
    Route::get('/notifications',[NotificationController::class,'index']); Route::get('/notifications/unread-count',[NotificationController::class,'unreadCount']);
    Route::patch('/notifications/read-all',[NotificationController::class,'markAllRead']);
    Route::patch('/notifications/{notification}/read',[NotificationController::class,'markAsReadIndividual']);
    Route::get('/trainer/notifications',[NotificationController::class,'trainerNotifications']);

    Route::get('/trainer/application',[TrainerApplicationController::class,'status']); Route::post('/trainer/application',[TrainerApplicationController::class,'store']); Route::post('/trainer/workspace/enter',[TrainerApplicationController::class,'enterWorkspace']); Route::post('/trainer/workspace/leave',[TrainerApplicationController::class,'leaveWorkspace']);

        Route::get('/member/membership/status', [MemberMembershipController::class, 'status']);
        Route::post('/member/membership/renew', [MemberMembershipController::class, 'renew']);
        Route::post('/member/membership/renew/{payment}/upload-proof', [MemberMembershipController::class, 'uploadProof']);
        Route::delete('/member/account', [MemberMembershipController::class, 'destroyAccount']);

    Route::middleware(EnsureActiveMembership::class)->group(function(){
        // Bookings
        Route::get('/bookings',[BookingController::class,'index']);
        Route::post('/bookings',[BookingController::class,'store']);
        Route::post('/bookings/{booking}/upload-proof',[BookingController::class,'uploadPaymentProof']);
        Route::patch('/bookings/{id}/status',[BookingController::class,'updateStatus']);
        Route::get('/bookings/{booking}/session-dates',[BookingController::class,'sessionDates']);

        // Workout Plans
        Route::delete('/workout-plans/clear-all', [WorkoutPlanController::class, 'clearAll']);
        Route::apiResource('workout-plans', WorkoutPlanController::class);

        // Chat
        Route::prefix('chat')->group(function(){
            Route::get('/contacts',[ChatController::class,'contacts']);
            Route::get('/messages',[ChatController::class,'messages']);
            Route::get('/messages/{message}/attachment',[ChatController::class,'attachment']);
            Route::post('/messages',[ChatController::class,'send']);
        });
    });

    Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store']);
    Route::delete('/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy']);

    Route::prefix('trainer')->middleware(EnsureTrainerWorkspaceAccess::class)->group(function(){
        Route::get('/member-monitoring/summary',[MemberFitnessMonitoringController::class,'summary']); Route::get('/member-monitoring/members',[MemberFitnessMonitoringController::class,'members']); Route::get('/member-monitoring/members/{member}',[MemberFitnessMonitoringController::class,'show']);
        Route::get('/incoming-rent-history/summary',[IncomingRentHistoryController::class,'summary']); Route::get('/incoming-rent-history/breakdown',[IncomingRentHistoryController::class,'breakdown']); Route::get('/incoming-rent-history',[IncomingRentHistoryController::class,'index']);
    });

    // Meal Plan & Food Log (member)
    Route::prefix('user')->middleware(EnsureActiveMembership::class)->group(function(){
        Route::get('/meal_plan', [App\Http\Controllers\MealPlanController::class, 'getMealPlan']);
        Route::put('/meal_plan', [App\Http\Controllers\MealPlanController::class, 'saveMealPlan']);
        Route::get('/food_log', [App\Http\Controllers\MealPlanController::class, 'getFoodLog']);
        Route::post('/food_log', [App\Http\Controllers\MealPlanController::class, 'addFood']);
        Route::delete('/food_log/{id}', [App\Http\Controllers\MealPlanController::class, 'deleteFood']);
    });

    Route::prefix('admin')->middleware(EnsureRole::class.':admin')->group(function(){
        Route::get('/roles',[UserManagementController::class,'roles']);
        Route::get('/landing-visits',[LandingVisitReportController::class,'index']); Route::get('/landing-visits/summary',[LandingVisitReportController::class,'summary']);
        Route::get('/auth-activity/summary',[AuthActivityReportController::class,'summary']); Route::get('/auth-activity/logs',[AuthActivityReportController::class,'logs']); Route::get('/auth-activity/registrations',[AuthActivityReportController::class,'registrations']);
        Route::get('/member-reports/summary',[MemberPaymentAttendanceReportController::class,'summary']); Route::get('/member-reports/payments',[MemberPaymentAttendanceReportController::class,'payments']); Route::get('/member-reports/attendance',[MemberPaymentAttendanceReportController::class,'attendance']);
        Route::get('/prospective-members',[ProspectiveMemberReviewController::class,'index']); Route::post('/prospective-members/{registration}/approve',[ProspectiveMemberReviewController::class,'approve']); Route::post('/prospective-members/{registration}/reject',[ProspectiveMemberReviewController::class,'reject']);
        Route::get('/trainer-applications',[TrainerApplicationReviewController::class,'index']); Route::post('/trainer-applications/{application}/approve',[TrainerApplicationReviewController::class,'approve']); Route::post('/trainer-applications/{application}/reject',[TrainerApplicationReviewController::class,'reject']); Route::get('/trainer-applications/{application}/documents/{type}',[TrainerApplicationReviewController::class,'download'])->whereIn('type',['cv','certificate']); Route::get('/trainer-applications/{application}/documents/{type}/stream',[TrainerApplicationReviewController::class,'stream'])->whereIn('type',['cv','certificate']);
        Route::get('/users/summary', [UserManagementController::class, 'summary']);
        Route::get('/nutrition-monitoring', [MemberPaymentAttendanceReportController::class, 'nutrition']);
        Route::apiResource('users',UserManagementController::class)->only(['index','store','update','destroy']); Route::apiResource('trainers',TrainerManagementController::class)->only(['index','store','update','destroy']); Route::apiResource('schedules',ScheduleManagementController::class)->parameters(['schedules'=>'schedule'])->only(['index','store','update','destroy']);
        
        // Booking Payment Management
        Route::get('/bookings/pending-payments', [BookingController::class, 'pendingPayments']);
        Route::post('/bookings/{booking}/confirm-payment', [BookingController::class, 'confirmPayment']);
        Route::post('/bookings/{booking}/reject-payment', [BookingController::class, 'rejectPayment']);
        Route::post('/bookings/auto-complete', [BookingController::class, 'autoCompleteExpiredBookings']);
        
        // Membership Renewal Review
        Route::get('/payments/pending-renewals', [MemberMembershipController::class, 'pendingRenewals']);
        Route::post('/payments/renewals/{payment}/confirm', [MemberMembershipController::class, 'confirmRenewal']);
        Route::post('/payments/renewals/{payment}/reject', [MemberMembershipController::class, 'rejectRenewal']);
        
        Route::get('/notifications', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'index']);
        Route::post('/approve/{id}', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'approve']);
        Route::post('/reject/{id}', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'reject']);

        // Excel Export
        Route::prefix('export')->group(function(){
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

    // SSE
    Route::get('/sse/{jobId}', [SseController::class, 'stream'])->name('sse.stream');

    // Excel Import
    Route::post('/excel/import', [ExcelImportController::class, 'upload']);

    // Attendance / Absen
    Route::prefix('attendance')->middleware(EnsureActiveMembership::class)->group(function(){
        Route::post('/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/history', [AttendanceController::class, 'myHistory']);
    });

    // Member Payments (own payments)
    Route::prefix('member/payments')->group(function(){
        Route::get('/', [MemberPaymentController::class, 'index']);
        Route::get('/summary', [MemberPaymentController::class, 'summary']);
        Route::post('/pay', [MemberPaymentController::class, 'pay']);
        Route::post('/simulate-create', [MemberPaymentController::class, 'simulateCreate']);
        Route::post('/simulate-pay', [MemberPaymentController::class, 'simulatePay']);
    });

    // Member Classes
    Route::prefix('member/classes')->middleware(EnsureActiveMembership::class)->group(function(){
        Route::get('/', [MemberClassesController::class, 'index']);
        Route::post('/{id}/join', [MemberClassesController::class, 'join']);
        Route::get('/my', [MemberClassesController::class, 'myClasses']);
    });
});
