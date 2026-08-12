<?php

use App\Http\Controllers\AnnualAssessmentController;
use App\Http\Controllers\DailyLogbookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\RecapController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\UnitController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('panduan', 'docs/Index')->name('panduan');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    Route::get('logbook', [DailyLogbookController::class, 'edit'])->name('logbook.edit');
    Route::get('logbook/{nurse}', [DailyLogbookController::class, 'edit'])->name('logbook.edit.nurse');
    Route::put('logbook/{nurse}', [DailyLogbookController::class, 'update'])->name('logbook.update');

    Route::get('recaps/monthly', [RecapController::class, 'monthly'])->name('recaps.monthly');
    Route::get('recaps/monthly/{nurse}', [RecapController::class, 'monthly'])->name('recaps.monthly.nurse');
    Route::get('recaps/annual', [RecapController::class, 'annual'])->name('recaps.annual');
    Route::get('recaps/annual/{nurse}', [RecapController::class, 'annual'])->name('recaps.annual.nurse');

    Route::get('assessments', [AnnualAssessmentController::class, 'index'])->name('assessments.index');
    Route::get('assessments/{nurse}', [AnnualAssessmentController::class, 'show'])->name('assessments.show');
    Route::put('assessments/{nurse}', [AnnualAssessmentController::class, 'update'])->name('assessments.update');
    Route::post('assessments/{nurse}/finalize', [AnnualAssessmentController::class, 'finalize'])->name('assessments.finalize');

    Route::get('print/logbook/{nurse}', [PrintController::class, 'logbook'])->name('print.logbook');
    Route::get('print/annual/{nurse}', [PrintController::class, 'annual'])->name('print.annual');
    Route::get('print/assessment/{assessment}', [PrintController::class, 'assessment'])->name('print.assessment');

    Route::get('units', [UnitController::class, 'index'])->name('units.index');
    Route::post('units', [UnitController::class, 'store'])->name('units.store');
    Route::patch('units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::delete('units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
    Route::get('units/export', [UnitController::class, 'export'])->name('units.export');
    Route::post('units/import', [UnitController::class, 'import'])->name('units.import');

    Route::get('nurses', [NurseController::class, 'index'])->name('nurses.index');
    Route::get('nurses/create', [NurseController::class, 'create'])->name('nurses.create');
    Route::post('nurses', [NurseController::class, 'store'])->name('nurses.store');
    Route::get('nurses/{nurse}/edit', [NurseController::class, 'edit'])->name('nurses.edit');
    Route::patch('nurses/{nurse}', [NurseController::class, 'update'])->name('nurses.update');
    Route::delete('nurses/{nurse}', [NurseController::class, 'destroy'])->name('nurses.destroy');
    Route::get('nurses/export', [NurseController::class, 'export'])->name('nurses.export');
    Route::post('nurses/import', [NurseController::class, 'import'])->name('nurses.import');

    Route::get('indicators', [IndicatorController::class, 'index'])->name('indicators.index');
    Route::post('indicators', [IndicatorController::class, 'store'])->name('indicators.store');
    Route::patch('indicators/{indicator}', [IndicatorController::class, 'update'])->name('indicators.update');
    Route::delete('indicators/{indicator}', [IndicatorController::class, 'destroy'])->name('indicators.destroy');
    Route::get('indicators/export', [IndicatorController::class, 'export'])->name('indicators.export');
    Route::post('indicators/import', [IndicatorController::class, 'import'])->name('indicators.import');
});

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
