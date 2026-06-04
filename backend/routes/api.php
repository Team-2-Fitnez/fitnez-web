<?php
use App\Http\Controllers\Admin\AuthActivityReportController;
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
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ManualPaymentMethodController;
use App\Http\Controllers\ManualProspectiveRegistrationController;
use App\Http\Controllers\MembershipPackageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TrainerApplicationController;
use App\Http\Controllers\TrainerBookingController;
use App\Http\Controllers\WorkoutPlanController;
use App\Http\Controllers\OtpController;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\JwtAuthenticate;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CookieConsentController;

Route::post('/cookie-consents', [CookieConsentController::class, 'store']);
Route::get('/landing',[LandingController::class,'index']);
Route::get('/membership-packages',[MembershipPackageController::class,'index']);
Route::get('/manual-payment-methods',[ManualPaymentMethodController::class,'index']);
Route::prefix('analytics')->group(function(){ Route::post('/landing-visit',[LandingVisitController::class,'store']); });
Route::prefix('auth')->group(function(){
    Route::post('/prospective-registration/start',[ManualProspectiveRegistrationController::class,'start']);
    Route::post('/prospective-registration/upload-proof',[ManualProspectiveRegistrationController::class,'uploadProof']);
    Route::get('/prospective-registration/status',[ManualProspectiveRegistrationController::class,'status']);
    Route::post('/register-prospective-member',[AuthController::class,'registerProspectiveMember']);
    Route::post('/request-login-otp',[AuthController::class,'requestLoginOtp']); Route::post('/verify-login-otp',[AuthController::class,'verifyLoginOtp']); Route::post('/password/forgot',[AuthController::class,'forgotPassword']); Route::post('/password/reset',[AuthController::class,'resetPassword']); Route::post('/login',[AuthController::class,'login']); Route::post('/member-login',[AuthController::class,'memberLogin']);
    Route::post('/otp/send',[OtpController::class,'send']); Route::post('/otp/verify',[OtpController::class,'verify']);
    Route::middleware(JwtAuthenticate::class)->group(function(){ Route::get('/me',[AuthController::class,'me']); Route::post('/logout',[AuthController::class,'logout']); });
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

    // Bookings
    Route::get('/bookings',[BookingController::class,'index']);
    Route::post('/bookings',[BookingController::class,'store']);
    Route::patch('/bookings/{id}/status',[BookingController::class,'updateStatus']);

    // Workout Plans
    Route::delete('/workout-plans/clear-all', [WorkoutPlanController::class, 'clearAll']);
    Route::apiResource('workout-plans', WorkoutPlanController::class);

    // Chat
    Route::prefix('chat')->group(function(){
        Route::get('/contacts',[ChatController::class,'contacts']);
        Route::get('/messages',[ChatController::class,'messages']);
        Route::post('/messages',[ChatController::class,'send']);
    });

    Route::prefix('trainer')->middleware(EnsureTrainerWorkspaceAccess::class)->group(function(){
        Route::get('/dashboard',[DashboardController::class,'trainerDashboard']);
        Route::get('/dashboard/summary',[DashboardController::class,'trainerSummary']);
        Route::get('/member-monitoring/summary',[MemberFitnessMonitoringController::class,'summary']); Route::get('/member-monitoring/members',[MemberFitnessMonitoringController::class,'members']); Route::get('/member-monitoring/members/{member}',[MemberFitnessMonitoringController::class,'show']);
        Route::get('/incoming-rent-history/summary',[IncomingRentHistoryController::class,'summary']); Route::get('/incoming-rent-history',[IncomingRentHistoryController::class,'index']);
        Route::get('/members/monitoring',[MemberFitnessMonitoringController::class,'members']);
        Route::get('/members/{member}/monitoring',[MemberFitnessMonitoringController::class,'show']);
        Route::get('/rent-history',[IncomingRentHistoryController::class,'index']);
    });

    // Meal Plan & Food Log (member)
    Route::prefix('user')->group(function(){
        Route::get('/meal_plan', [App\Http\Controllers\MealPlanController::class, 'getMealPlan']);
        Route::put('/meal_plan', [App\Http\Controllers\MealPlanController::class, 'saveMealPlan']);
        Route::get('/food_log', [App\Http\Controllers\MealPlanController::class, 'getFoodLog']);
        Route::post('/food_log', [App\Http\Controllers\MealPlanController::class, 'addFood']);
        Route::delete('/food_log/{id}', [App\Http\Controllers\MealPlanController::class, 'deleteFood']);
    });

    // Admin Nutrition Monitoring
    Route::prefix('admin')->group(function(){
        Route::get('/nutrition-monitoring', [App\Http\Controllers\MealPlanController::class, 'adminMonitoring']);
    });

    Route::prefix('admin')->middleware(EnsureRole::class.':admin')->group(function(){
        Route::get('/dashboard',[DashboardController::class,'adminDashboard']);
        Route::get('/dashboard/summary',[DashboardController::class,'adminSummary']);
        Route::get('/roles',[UserManagementController::class,'roles']);
        Route::get('/landing-visits',[LandingVisitReportController::class,'index']); Route::get('/landing-visits/summary',[LandingVisitReportController::class,'summary']);
        Route::get('/auth-activity/summary',[AuthActivityReportController::class,'summary']); Route::get('/auth-activity/logs',[AuthActivityReportController::class,'logs']); Route::get('/auth-activity/registrations',[AuthActivityReportController::class,'registrations']);
        Route::get('/member-reports/summary',[MemberPaymentAttendanceReportController::class,'summary']); Route::get('/member-reports/payments',[MemberPaymentAttendanceReportController::class,'payments']); Route::get('/member-reports/attendance',[MemberPaymentAttendanceReportController::class,'attendance']);
        Route::get('/reports/auth-activity',[AuthActivityReportController::class,'logs']);
        Route::get('/reports/registrations',[AuthActivityReportController::class,'registrations']);
        Route::get('/reports/payments',[MemberPaymentAttendanceReportController::class,'payments']);
        Route::get('/reports/attendance',[MemberPaymentAttendanceReportController::class,'attendance']);
        Route::get('/reports/member-payments',[MemberPaymentAttendanceReportController::class,'payments']);
        Route::get('/reports/member-attendance',[MemberPaymentAttendanceReportController::class,'attendance']);
        Route::get('/reports/member-payments/summary',[MemberPaymentAttendanceReportController::class,'summary']);
        Route::get('/reports/member-attendance/summary',[MemberPaymentAttendanceReportController::class,'summary']);
        Route::get('/prospective-members',[ProspectiveMemberReviewController::class,'index']); Route::post('/prospective-members/{registration}/approve',[ProspectiveMemberReviewController::class,'approve']); Route::post('/prospective-members/{registration}/reject',[ProspectiveMemberReviewController::class,'reject']);
        Route::get('/trainer-applications',[TrainerApplicationReviewController::class,'index']); Route::post('/trainer-applications/{application}/approve',[TrainerApplicationReviewController::class,'approve']); Route::post('/trainer-applications/{application}/reject',[TrainerApplicationReviewController::class,'reject']); Route::get('/trainer-applications/{application}/documents/{type}',[TrainerApplicationReviewController::class,'download'])->whereIn('type',['cv','certificate']);
        Route::apiResource('users',UserManagementController::class)->only(['index','store','update','destroy']); Route::apiResource('trainers',TrainerManagementController::class)->only(['index','store','update','destroy']); Route::apiResource('schedules',ScheduleManagementController::class)->parameters(['schedules'=>'schedule'])->only(['index','store','update','destroy']);
        
        Route::get('/notifications', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'index']);
        Route::post('/approve/{id}', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'approve']);
        Route::post('/reject/{id}', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'reject']);
    });
});
