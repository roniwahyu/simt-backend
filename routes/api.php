<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\AuthController;
use Modules\Core\Http\Controllers\PortalOrtuApiController;
use App\Http\Middleware\IdentifyTenant;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/v1/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/v1/auth/parent-login', [PortalOrtuApiController::class, 'parentLogin'])->middleware('throttle:5,1');
Route::post('/v1/auth/student-login', [PortalOrtuApiController::class, 'studentLogin'])->middleware('throttle:5,1');
Route::get('/v1/ping', [AuthController::class, 'ping'])->middleware([IdentifyTenant::class, 'throttle:60,1']);
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'simt-mvp-api']);
});

// Authenticated routes
Route::middleware(['auth:sanctum', IdentifyTenant::class, 'check.tenant.access', 'throttle:60,1'])->group(function () {
    Route::get('/v1/me', [AuthController::class, 'me']);
    Route::post('/v1/logout', [AuthController::class, 'logout']);
    Route::get('/v1/me/children', [AuthController::class, 'children']);

    // Portal Ortu Dashboard & Grade Details Endpoints
    Route::get('/v1/portal/students/{student}/dashboard', [PortalOrtuApiController::class, 'dashboard']);
    Route::get('/v1/portal/students/{student}/student-dashboard', [PortalOrtuApiController::class, 'studentDashboard']);
    Route::get('/v1/portal/students/{student}/subjects/{subject}/grade-details', [PortalOrtuApiController::class, 'gradeDetails']);
});

