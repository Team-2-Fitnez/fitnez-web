<?php

use App\Http\Middleware\JwtAuthenticate;
use Illuminate\Support\Facades\Route;

Route::middleware(JwtAuthenticate::class)->group(function () {
    require __DIR__ . '/protected/dashboard.php';
    require __DIR__ . '/protected/browser.php';
    require __DIR__ . '/protected/notifications.php';
    require __DIR__ . '/protected/trainer-application.php';
    require __DIR__ . '/protected/membership.php';
    require __DIR__ . '/protected/booking.php';
    require __DIR__ . '/protected/workout.php';
    require __DIR__ . '/protected/chat.php';
    require __DIR__ . '/protected/push.php';
    require __DIR__ . '/protected/trainer-workspace.php';
    require __DIR__ . '/protected/meal-plan.php';
    require __DIR__ . '/protected/admin.php';
    require __DIR__ . '/protected/sse.php';
    require __DIR__ . '/protected/excel-import.php';
    require __DIR__ . '/protected/attendance.php';
    require __DIR__ . '/protected/member-payments.php';
    require __DIR__ . '/protected/member-classes.php';
});
