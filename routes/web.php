<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\PengajuanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [LoginController::class, 'show']);
Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::get('/dealers/options', [PengajuanController::class, 'dealers']);

    Route::get('/pengajuan', [PengajuanController::class, 'index']);
    Route::get('/pengajuan/datatable', [PengajuanController::class, 'datatable']);
    Route::get('/pengajuan/create', [PengajuanController::class, 'create']);
    Route::post('/pengajuan', [PengajuanController::class, 'store']);
    Route::where(['public_id' => '[a-z0-9]{6}'])->group(function () {
        Route::get('/pengajuan/{public_id}/json', [PengajuanController::class, 'json']);
        Route::get('/pengajuan/{public_id}/edit', [PengajuanController::class, 'edit']);
        Route::get('/pengajuan/{public_id}/cetak-kontrak', [PengajuanController::class, 'cetakKontrak']);
        Route::get('/pengajuan/{public_id}/cetak-po', [PengajuanController::class, 'cetakPo']);
        Route::get('/pengajuan/{public_id}', [PengajuanController::class, 'show']);
        Route::put('/pengajuan/{public_id}', [PengajuanController::class, 'update']);
        Route::delete('/pengajuan/{public_id}', [PengajuanController::class, 'destroy']);
        Route::post('/pengajuan/{public_id}/submit', [PengajuanController::class, 'submit']);
        Route::post('/pengajuan/{public_id}/approve', [PengajuanController::class, 'approve']);
        Route::post('/pengajuan/{public_id}/reject', [PengajuanController::class, 'reject']);
        Route::post('/pengajuan/{public_id}/print', [PengajuanController::class, 'markPrinted']);
        Route::post('/pengajuan/{public_id}/disburse', [PengajuanController::class, 'disburse']);
        Route::post('/pengajuan/{public_id}/dokumen', [DokumenController::class, 'store']);
    });

    Route::get('/dokumen/{id}/file', [DokumenController::class, 'file']);
    Route::delete('/dokumen/{id}', [DokumenController::class, 'destroy']);
});
