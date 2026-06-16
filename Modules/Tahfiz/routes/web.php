<?php

use Illuminate\Support\Facades\Route;
use Modules\Tahfiz\Http\Controllers\TahfizController;
use App\Http\Middleware\SetTenantFromUser;

Route::middleware(['auth', SetTenantFromUser::class, 'module.active:Tahfiz'])->group(function () {
    Route::get('/tahfiz', [TahfizController::class, 'index'])->name('tahfiz.index');
});
