<?php

use App\Http\Controllers\DestinationController;
use App\Http\Controllers\DigestController;
use App\Http\Controllers\DigestRunController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', \App\Http\Controllers\DashboardController::class)->name('dashboard');

    // Digests
    Route::resource('digests', DigestController::class);
    Route::patch('digests/{digest}/toggle', [DigestController::class, 'toggle'])
        ->name('digests.toggle');
    Route::get('digests/{digest}/runs/{run}', [DigestRunController::class, 'show'])
        ->name('digests.runs.show');

    // Destinations
    Route::resource('destinations', DestinationController::class)->except(['show']);
    Route::patch('destinations/{destination}/toggle', [DestinationController::class, 'toggle'])
        ->name('destinations.toggle');
});

require __DIR__.'/settings.php';
