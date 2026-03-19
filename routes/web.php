<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapController;
use App\Http\Controllers\AdminAlumniController;

Route::get('/', [MapController::class, 'index']);

// ROUTE HALAMAN ADMIN
Route::prefix('admin')->group(function () {
    // Halaman Tabel Daftar Alumni
    Route::get('/alumni', [AdminAlumniController::class, 'index'])->name('admin.alumni.index');
    
    Route::get('/alumni/create', [AdminAlumniController::class, 'create'])->name('admin.alumni.create');
    Route::post('/alumni/store', [AdminAlumniController::class, 'store'])->name('admin.alumni.store');

});
Route::post('/admin/check-nim', [AdminAlumniController::class, 'checkNim'])
    ->name('admin.checkNim');

Route::get('/admin/alumni/import', [AdminAlumniController::class,'importPage'])
    ->name('admin.alumni.import');

Route::post('/admin/alumni/import-preview', [AdminAlumniController::class,'importPreview'])
    ->name('admin.alumni.import.preview');

Route::post('/admin/alumni/import-store', [AdminAlumniController::class,'importStore'])
    ->name('admin.alumni.import.store');