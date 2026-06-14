<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceController;
use Modules\Attendance\Http\Controllers\FinanceController;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SetTenantFromUser;

Route::middleware(['auth', IdentifyTenant::class, SetTenantFromUser::class])->group(function () {
    // Attendance (module: Attendance)
    Route::middleware('module.active:Attendance')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/class/{class}', [AttendanceController::class, 'classGrid'])->name('attendance.grid');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance/rekap', [AttendanceController::class, 'rekap'])->name('attendance.rekap');
    });

    // Finance (module: Finance — but for now grouped under Attendance module)
    Route::middleware('module.active:Finance')->group(function () {
        Route::get('/finance/bills', [FinanceController::class, 'bills'])->name('finance.bills');
        Route::post('/finance/bills/generate', [FinanceController::class, 'generateBills'])->name('finance.bills.generate');
        Route::post('/bills/{bill}/payment', [FinanceController::class, 'recordPayment'])->name('finance.payment.store');
        Route::get('/payments/{payment}/receipt', [FinanceController::class, 'printReceipt'])->name('finance.receipt');
        Route::post('/finance/reminders', [FinanceController::class, 'sendReminders'])->name('finance.reminders');
    });
});
