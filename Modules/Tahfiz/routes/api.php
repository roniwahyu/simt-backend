<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IdentifyTenant;

Route::middleware(['auth:sanctum', IdentifyTenant::class, 'check.tenant.access', 'module.active:Tahfiz'])->group(function () {
    // Dynamic endpoints for Tahfiz can go here in the future
});
