<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\AuthController;
use Modules\Core\Http\Controllers\DashboardController;
use Modules\Core\Http\Controllers\SuperAdminController;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SetTenantFromUser;

/*
|--------------------------------------------------------------------------
| Super Admin Routes (panel.simt.id or /admin)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('super.dashboard');
    Route::get('/tenants/create', [SuperAdminController::class, 'createTenant'])->name('super.tenant.create');
    Route::post('/tenants', [SuperAdminController::class, 'storeTenant'])->name('super.tenant.store');
    Route::get('/tenants/{tenant}/edit', [SuperAdminController::class, 'editTenant'])->name('super.tenant.edit');
    Route::put('/tenants/{tenant}', [SuperAdminController::class, 'updateTenant'])->name('super.tenant.update');
});

/*
|--------------------------------------------------------------------------
| Tenant Web Routes (subdomain or / with tenant header)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', IdentifyTenant::class, SetTenantFromUser::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::get('/', function () {
    return view('welcome');
});

// Login routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
