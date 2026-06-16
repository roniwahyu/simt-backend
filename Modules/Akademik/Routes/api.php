<?php

use Illuminate\Support\Facades\Route;
use Modules\Akademik\Http\Controllers\AkademikApiController;
use App\Http\Middleware\IdentifyTenant;

/*
|--------------------------------------------------------------------------
| Akademik API Routes — untuk Portal Ortu (Next.js)
|--------------------------------------------------------------------------
|
| Endpoint: GET /api/v1/students/{student}/grades
| Endpoint: GET /api/v1/students/{student}/rapor
|
*/

Route::middleware(['auth:sanctum', IdentifyTenant::class, 'check.tenant.access', 'module.active:Akademik'])->group(function () {
    Route::get('/v1/students/{student}/grades', [AkademikApiController::class, 'grades']);
    Route::get('/v1/students/{student}/rapor', [AkademikApiController::class, 'rapor']);
});
