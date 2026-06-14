<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationController;
use App\Http\Middleware\SetTenantFromUser;

Route::middleware(['auth', SetTenantFromUser::class])->group(function () {
    Route::get('/admin/notification/connect', [NotificationController::class, 'connect'])->name('notification.connect');
    Route::post('/admin/notification/session/start', [NotificationController::class, 'startSession'])->name('notification.session.start');
    Route::post('/admin/notification/session/stop', [NotificationController::class, 'stopSession'])->name('notification.session.stop');
    Route::get('/admin/notification/session/status', [NotificationController::class, 'sessionStatus'])->name('notification.session.status');
    Route::get('/admin/notification/table', [NotificationController::class, 'notificationsTable'])->name('notification.table');
    Route::get('/admin/notification/tools', [NotificationController::class, 'tools'])->name('notification.tools');
    Route::post('/admin/notification/tools/send', [NotificationController::class, 'toolsSend'])->name('notification.tools.send');
    Route::get('/admin/notification/incoming-feed', [NotificationController::class, 'incomingFeed'])->name('notification.incoming_feed');
});
