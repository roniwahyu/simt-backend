<?php

use Illuminate\Support\Facades\Route;
use Modules\Akademik\Http\Controllers\AkademikController;
use Modules\Akademik\Http\Controllers\GradeController;
use App\Http\Middleware\SetTenantFromUser;

Route::middleware(['auth', SetTenantFromUser::class, 'module.active:Akademik'])->group(function () {
    // Dashboard Akademik
    Route::get('/akademik', [AkademikController::class, 'index'])->name('akademik.index');

    // Rombel (Classes)
    Route::get('/akademik/classes', [AkademikController::class, 'classes'])->name('akademik.classes');
    Route::post('/akademik/classes', [AkademikController::class, 'storeClass'])->name('akademik.classes.store');

    // Mapel (Subjects)
    Route::get('/akademik/subjects', [AkademikController::class, 'subjects'])->name('akademik.subjects');
    Route::post('/akademik/subjects', [AkademikController::class, 'storeSubject'])->name('akademik.subjects.store');

    // Nilai (Grades)
    Route::get('/akademik/grades', [GradeController::class, 'index'])->name('grades.index');
    Route::get('/akademik/grades/create', [GradeController::class, 'create'])->name('grades.create');
    Route::post('/akademik/grades', [GradeController::class, 'store'])->name('grades.store');
    Route::get('/akademik/grades/rapor', [GradeController::class, 'rapor'])->name('grades.rapor');
    Route::get('/akademik/grades/{grade}', [GradeController::class, 'show'])->name('grades.show');
    Route::put('/akademik/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');
});
