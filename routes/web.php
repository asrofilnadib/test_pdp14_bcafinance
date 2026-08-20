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
    Route::get('/pengajuan/{id}/json', [PengajuanController::class, 'json']);
    Route::get('/pengajuan/{id}/edit', [PengajuanController::class, 'edit']);
    Route::get('/pengajuan/{id}/cetak-kontrak', [PengajuanController::class, 'cetakKontrak']);
    Route::get('/pengajuan/{id}/cetak-po', [PengajuanController::class, 'cetakPo']);
    Route::get('/pengajuan/{id}', [PengajuanController::class, 'show']);
    Route::put('/pengajuan/{id}', [PengajuanController::class, 'update']);
    Route::delete('/pengajuan/{id}', [PengajuanController::class, 'destroy']);
    Route::post('/pengajuan/{id}/submit', [PengajuanController::class, 'submit']);
    Route::post('/pengajuan/{id}/approve', [PengajuanController::class, 'approve']);
    Route::post('/pengajuan/{id}/reject', [PengajuanController::class, 'reject']);
    Route::post('/pengajuan/{id}/print', [PengajuanController::class, 'markPrinted']);
    Route::post('/pengajuan/{id}/disburse', [PengajuanController::class, 'disburse']);
    Route::post('/pengajuan/{id}/dokumen', [DokumenController::class, 'store']);

    Route::get('/dokumen/{id}/file', [DokumenController::class, 'file']);
    Route::delete('/dokumen/{id}', [DokumenController::class, 'destroy']);
});
