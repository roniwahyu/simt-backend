<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationController;

Route::prefix('v1')->group(function () {
    Route::post('/wa/delivery-callback', [NotificationController::class, 'deliveryCallback'])->name('notification.delivery_callback');
});
