<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceController;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SetTenantFromUser;

Route::middleware(['auth', SetTenantFromUser::class])->group(function () {
    // Attendance (module: Attendance) — Finance kini modul terpisah (Modules/Finance).
    Route::middleware('module.active:Attendance')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/class/{class}/{date?}', [AttendanceController::class, 'classGrid'])
            ->name('attendance.grid')
            ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance/rekap', [AttendanceController::class, 'rekap'])->name('attendance.rekap');
        Route::get('/attendance/rekap/export', [AttendanceController::class, 'exportRecap'])->name('attendance.rekap.export');
    });
});
