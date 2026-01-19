<?php

use App\Http\Controllers\DestinationController;
use App\Http\Controllers\DigestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Digests
    Route::resource('digests', DigestController::class)->except(['show']);
    Route::patch('digests/{digest}/toggle', [DigestController::class, 'toggle'])
        ->name('digests.toggle');

    // Destinations
    Route::resource('destinations', DestinationController::class)->except(['show']);
    Route::patch('destinations/{destination}/toggle', [DestinationController::class, 'toggle'])
        ->name('destinations.toggle');
});

require __DIR__.'/settings.php';
