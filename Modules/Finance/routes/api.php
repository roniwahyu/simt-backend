<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceApiController;
use App\Http\Middleware\IdentifyTenant;

/*
|--------------------------------------------------------------------------
| Finance API Routes — untuk Portal Ortu (Next.js)
|--------------------------------------------------------------------------
|
| Endpoint: GET /api/v1/students/{student}/bills
| Untuk wali melihat tagihan & riwayat bayar anak mereka.
| Di-gate module.active:Finance — tenant tanpa langganan → 403 MODULE_INACTIVE.
|
*/

Route::middleware(['auth:sanctum', IdentifyTenant::class, 'check.tenant.access', 'module.active:Finance'])->group(function () {
    Route::get('/v1/students/{student}/bills', [FinanceApiController::class, 'index']);
});
