<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\AuthController;
use App\Http\Middleware\IdentifyTenant;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/v1/auth/login', [AuthController::class, 'login']);
Route::get('/v1/ping', [AuthController::class, 'ping'])->middleware(IdentifyTenant::class);
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'simt-mvp-api']);
});

// Authenticated routes
Route::middleware(['auth:sanctum', IdentifyTenant::class, 'check.tenant.access'])->group(function () {
    Route::get('/v1/me', [AuthController::class, 'me']);
    Route::post('/v1/logout', [AuthController::class, 'logout']);
    Route::get('/v1/me/children', [AuthController::class, 'children']);
});
