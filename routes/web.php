<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapController;
use App\Http\Controllers\AdminAlumniController;
use App\Http\Controllers\StatistikController;
use App\Http\Controllers\PublicStatistikController;
use App\Http\Controllers\NominatimController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\LandingController;

/*
|--------------------------------------------------------------------------
| PUBLIC MAP
|--------------------------------------------------------------------------
*/

Route::get('/peta', [MapController::class, 'index'])->name('peta');
Route::get('/map/data', [MapController::class, 'data'])->name('map.data');

/*
|--------------------------------------------------------------------------
| PUBLIC STATISTIK
|--------------------------------------------------------------------------
*/

Route::get('/statistik', [PublicStatistikController::class, 'index'])->name('statistik.index');
Route::get('/statistik/data', [PublicStatistikController::class, 'data'])->name('statistik.data');

/*
|--------------------------------------------------------------------------
| NOMINATIM TEST (ADMIN TOOL)
|--------------------------------------------------------------------------
*/

Route::view('/nominatim', 'nominatim')->name('nominatim');
Route::get('/nominatim/reverse', [NominatimController::class, 'reverse'])->name('nominatim.reverse');

/*
|--------------------------------------------------------------------------
| SPATIAL DATA API
|--------------------------------------------------------------------------
*/

Route::get('/api/wilayah-kalsel', [WilayahController::class, 'index'])->name('api.wilayah-kalsel');


/*
|--------------------------------------------------------------------------
| LANDING PAGE
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::redirect('/welcome', '/', 301);


/*
|--------------------------------------------------------------------------
| ADMIN PANEL
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {

    Route::redirect('/', '/admin/statistik')
        ->name('admin.index');

    Route::get('/geocode', [AdminAlumniController::class, 'geocode'])
        ->name('admin.geocode');
    /*
    |--------------------------------------------------------------------------
    | CRUD ALUMNI
    |--------------------------------------------------------------------------
    */

    Route::get('/alumni', [AdminAlumniController::class, 'index'])
        ->name('admin.alumni.index');

    Route::get('/alumni/create', [AdminAlumniController::class, 'create'])
        ->name('admin.alumni.create');

    Route::post('/alumni/store', [AdminAlumniController::class, 'store'])
        ->name('admin.alumni.store');

    Route::get('/alumni/{id}/edit', [AdminAlumniController::class, 'edit'])
        ->name('admin.alumni.edit');

    Route::put('/alumni/{id}', [AdminAlumniController::class, 'update'])
        ->name('admin.alumni.update');

    Route::delete('/alumni', [AdminAlumniController::class, 'bulkDestroy'])
        ->name('admin.alumni.bulk-destroy');

    Route::delete('/alumni/{id}', [AdminAlumniController::class, 'destroy'])
        ->name('admin.alumni.destroy');


    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    Route::post('/check-nim', [AdminAlumniController::class, 'checkNim'])
        ->name('admin.checkNim');


    /*
    |--------------------------------------------------------------------------
    | IMPORT EXCEL
    |--------------------------------------------------------------------------
    */

    Route::get('/alumni/import', [AdminAlumniController::class, 'importPage'])
        ->name('admin.alumni.import');

    Route::get('/alumni/import/template', [AdminAlumniController::class, 'downloadTemplate'])
        ->name('admin.alumni.import.template');

    Route::post('/alumni/import-preview', [AdminAlumniController::class, 'importPreview'])
        ->name('admin.alumni.import.preview');

    Route::post('/alumni/import-store', [AdminAlumniController::class, 'importStore'])
        ->name('admin.alumni.import.store');


    /*
    |--------------------------------------------------------------------------
    | RIWAYAT PEKERJAAN
    |--------------------------------------------------------------------------
    */

    Route::post('/alumni/{id}/pekerjaan', [AdminAlumniController::class, 'storePekerjaan'])
        ->name('admin.pekerjaan.store');

    Route::put('/pekerjaan/{id}/status', [AdminAlumniController::class, 'updateStatusKerja'])
        ->name('admin.pekerjaan.updateStatus');

    Route::put('/pekerjaan/{id}', [AdminAlumniController::class, 'updatePekerjaan'])
        ->name('admin.pekerjaan.update');

    Route::delete('/pekerjaan/{id}', [AdminAlumniController::class, 'destroyPekerjaan'])
        ->name('admin.pekerjaan.destroy');


    /*
    |--------------------------------------------------------------------------
    | STUDI LANJUT
    |--------------------------------------------------------------------------
    */

    Route::post('/alumni/{alumni}/studi-lanjut', [AdminAlumniController::class, 'storeStudiLanjut'])
        ->name('admin.studi-lanjut.store');

    Route::put('/alumni/{alumni}/studi-lanjut/{studiLanjut}', [AdminAlumniController::class, 'updateStudiLanjut'])
        ->name('admin.studi-lanjut.update');

    Route::delete('/alumni/{alumni}/studi-lanjut/{studiLanjut}', [AdminAlumniController::class, 'destroyStudiLanjut'])
        ->name('admin.studi-lanjut.destroy');


    /*
    |--------------------------------------------------------------------------
    | STATISTIK
    |--------------------------------------------------------------------------
    */

    Route::get('/statistik', [StatistikController::class, 'index'])
        ->name('admin.statistik');

    Route::get('/statistik/data', [StatistikController::class, 'data'])
        ->name('admin.statistik.data');

    Route::get('/statistik/export/pdf', [StatistikController::class, 'exportPdf'])
        ->name('admin.statistik.export.pdf');

    Route::get('/statistik/export/excel', [StatistikController::class, 'exportExcel'])
        ->name('admin.statistik.export.excel');

});
