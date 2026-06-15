<?php

use App\Features\Notifications\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/notifications', [NotificationController::class, 'index']);
Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsReadIndividual']);
Route::get('/trainer/notifications', [NotificationController::class, 'trainerNotifications']);
