<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceApiController;
use App\Http\Middleware\IdentifyTenant;

Route::middleware(['auth:sanctum', IdentifyTenant::class, 'check.tenant.access', 'module.active:Attendance'])->group(function () {
    Route::get('/v1/students/{student}/attendances', [AttendanceApiController::class, 'index']);
});
