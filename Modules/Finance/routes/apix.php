<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IdentifyTenant;

// Endpoint API Keuangan untuk Portal Ortu (Doc 39): tagihan & riwayat bayar anak.
// Di-gate module.active:Finance — tenant tanpa langganan → 403 MODULE_INACTIVE.
Route::middleware(['auth:sanctum', IdentifyTenant::class, 'check.tenant.access', 'module.active:Finance'])->group(function () {
    // Placeholder kontrak: GET /api/v1/students/{student}/bills (implementasi Sprint 5)
    // Route::get('/v1/students/{student}/bills', [FinanceApiController::class, 'index']);
});
