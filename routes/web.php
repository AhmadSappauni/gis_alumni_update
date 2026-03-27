<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapController;
use App\Http\Controllers\AdminAlumniController;

Route::get('/', [MapController::class, 'index']);

// ROUTE HALAMAN ADMIN
Route::prefix('admin')->group(function () {
    // Alumni CRUD
    Route::get('/alumni', [AdminAlumniController::class, 'index'])->name('admin.alumni.index');
    Route::get('/alumni/create', [AdminAlumniController::class, 'create'])->name('admin.alumni.create');
    Route::post('/alumni/store', [AdminAlumniController::class, 'store'])->name('admin.alumni.store');
    Route::get('/alumni/{nim}/edit', [AdminAlumniController::class, 'edit'])->name('admin.alumni.edit');
    Route::put('/alumni/{nim}', [AdminAlumniController::class, 'update'])->name('admin.alumni.update');
    Route::delete('/alumni/{nim}', [AdminAlumniController::class, 'destroy'])->name('admin.alumni.destroy');

    // Fitur Tambahan Alumni
    Route::post('/check-nim', [AdminAlumniController::class, 'checkNim'])->name('admin.checkNim');
    
    // Import Excel
    Route::get('/alumni/import', [AdminAlumniController::class, 'importPage'])->name('admin.alumni.import');
    Route::post('/alumni/import-preview', [AdminAlumniController::class, 'importPreview'])->name('admin.alumni.import.preview');
    Route::post('/alumni/import-store', [AdminAlumniController::class, 'importStore'])->name('admin.alumni.import.store');

    // Manajemen Pekerjaan (Multi-job)
    Route::post('/alumni/{nim}/pekerjaan', [AdminAlumniController::class, 'storePekerjaan'])->name('admin.pekerjaan.store');
    
    // Gunakan POST jika di Blade pakai Form, gunakan GET jika di Blade pakai tag <a>
    Route::post('/pekerjaan/{id}/update-status', [AdminAlumniController::class, 'updateStatusKerja'])->name('admin.pekerjaan.updateStatus');
    Route::delete('/pekerjaan/{id}', [AdminAlumniController::class, 'destroyPekerjaan'])->name('admin.pekerjaan.destroy');
    Route::put('/pekerjaan/{id}', [AdminAlumniController::class, 'updatePekerjaan'])->name('admin.pekerjaan.update');
    
    // ROUTE BARU: Statistik Peta (Akan kita buat)
    Route::get('/statistik', [AdminAlumniController::class, 'statistikPeta'])->name('admin.statistik.peta');
});