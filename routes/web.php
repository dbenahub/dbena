<?php

declare(strict_types=1);

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OwnerReportPdfController;
use App\Http\Controllers\ProjectExportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SheetWebhookController;
use App\Livewire\Admin\ConfigPanel;
use App\Livewire\Auth\AdminLoginFlow;
use App\Livewire\Auth\UserLoginFlow;
use App\Livewire\Dashboard\Laporan;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Dashboard\ServiceDetail;
use App\Livewire\Dashboard\Tetapan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::post('/locale/{locale}', [LocaleController::class, 'update'])
    ->whereIn('locale', ['ms', 'en'])
    ->name('locale.update');

/*
 * Webhook Google Apps Script — dipanggil oleh sheet itu sendiri.
 * Tiada sesi; disahkan oleh token per-integrasi dan dihadkan kadar.
 */
Route::post('/sheets/webhook/{integration}/{token}', SheetWebhookController::class)
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('sheets.webhook');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', UserLoginFlow::class)->name('login');
    Route::get('/admin/login', AdminLoginFlow::class)->name('admin.login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('dashboard')->group(function (): void {
    Route::get('/', Overview::class)->name('dashboard');
    Route::get('/servis/{key}', ServiceDetail::class)->name('service.detail');
    Route::get('/laporan', Laporan::class)->name('laporan');
    Route::get('/laporan/eksport', ReportExportController::class)->name('laporan.export');
    Route::get('/laporan/pemilik', \App\Livewire\Dashboard\OwnerReport::class)->name('laporan.owner');
    Route::get('/laporan/pemilik/pdf', OwnerReportPdfController::class)->name('laporan.owner.pdf');
    Route::get('/tetapan', Tetapan::class)->name('tetapan');

    // Master List of Project — paparan sahaja; eksport dilindungi gate.
    Route::get('/projek', \App\Livewire\Dashboard\ProjectList::class)->name('projek');
    Route::get('/projek/eksport', ProjectExportController::class)->name('projek.eksport');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function (): void {
    Route::get('/', ConfigPanel::class)->name('admin.panel');
    Route::get('/sheets', \App\Livewire\Admin\SheetManager::class)->name('admin.sheets');
    Route::get('/roadmap', \App\Livewire\Admin\RoadmapEditor::class)->name('admin.roadmap');
});
