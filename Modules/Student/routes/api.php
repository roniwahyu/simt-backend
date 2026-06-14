<?php

use Illuminate\Support\Facades\Route;
use Modules\Student\Http\Controllers\StudentApiController;
use App\Http\Middleware\IdentifyTenant;

Route::middleware(['auth:sanctum', IdentifyTenant::class, 'check.tenant.access', 'module.active:Student'])->group(function () {
    Route::get('/v1/students', [StudentApiController::class, 'list']);
    Route::get('/v1/students/{student}', [StudentApiController::class, 'show']);
});
