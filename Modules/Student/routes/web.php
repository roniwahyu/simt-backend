<?php

use Illuminate\Support\Facades\Route;
use Modules\Student\Http\Controllers\StudentController;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SetTenantFromUser;

Route::middleware(['auth', IdentifyTenant::class, SetTenantFromUser::class, 'module.active:Student'])->group(function () {
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::get('/students/import', [StudentController::class, 'importForm'])->name('students.import.form');
    Route::post('/students/import/upload', [StudentController::class, 'importUpload'])->name('students.import.upload');
    Route::post('/students/import/commit', [StudentController::class, 'importCommit'])->name('students.import.commit');
});
