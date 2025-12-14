<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/import/local', [DashboardController::class, 'importLocal'])->name('dashboard.import.local');
    Route::post('/dashboard/import/remote', [DashboardController::class, 'importFromKoha'])->name('dashboard.import.remote');
    Route::post('/dashboard/sync', [DashboardController::class, 'syncTranslations'])->name('dashboard.sync');
    Route::post('/dashboard/export', [DashboardController::class, 'exportTranslations'])->name('dashboard.export');
    Route::post('/dashboard/push', [DashboardController::class, 'pushToKoha'])->name('dashboard.push');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/translations', [TranslationController::class, 'index'])->name('translations.index');
    Route::get('/translations/{translation}/edit', [TranslationController::class, 'edit'])->name('translations.edit');
    Route::put('/translations/{translation}', [TranslationController::class, 'update'])->name('translations.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
