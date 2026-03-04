<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Service\ActivityController as ServiceActivityController;
use App\Http\Controllers\Service\DashboardController as ServiceDashboardController;
use App\Http\Controllers\Service\QuranController;
use App\Http\Controllers\Service\DzikirDoaController;
use App\Http\Controllers\Service\HadistController;
use App\Http\Controllers\Service\JadwalSholatController;
use App\Http\Controllers\Service\PosterDakwahController;

Route::get('dashboard', [ServiceDashboardController::class, 'index'])->name('service.dashboard.index');

Route::prefix('activity')->group(function () {
    Route::get('/', [ServiceActivityController::class, 'index'])->name('service.activity.index');
    Route::post('/', [ServiceActivityController::class, 'get'])->name('service.activity.get');
});

Route::prefix('quran')->group(function () {
    Route::get('/', [QuranController::class, 'quran'])->name('service.quran.index');
    Route::get('/{id}', [QuranController::class, 'quranById'])->name('service.quranById');
});

Route::prefix('dzikir-doa')->group(function () {
    Route::get('/', [DzikirDoaController::class, 'index'])->name('service.dzikirDoa');
    Route::get('/{slug}', [DzikirDoaController::class, 'dzikirDoaById'])->name('service.dzikirDoaById');
});

Route::prefix('hadist')->group(function () {
    Route::get('/', [HadistController::class, 'hadist'])->name('service.hadist');
    Route::get('/{id}', [HadistController::class, 'hadistById'])->name('service.hadistById');
    Route::get('/{id}/search', [HadistController::class, 'hadistSearch'])->name('service.hadistSearch');
});

Route::prefix('jadwal-sholat')->group(function () {
    Route::get('/', [JadwalSholatController::class, 'index'])->name('service.jadwalSholat');
    Route::get('/lokasi', [JadwalSholatController::class, 'getJadwalSholat']);
});

Route::prefix('poster-dakwah')->group(function () {
    Route::get('/', [PosterDakwahController::class, 'index'])->name('service.posterDakwah');
});
