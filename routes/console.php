<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

// Fetch GitHub repository releases every 15 minutes
Schedule::command('sources:fetch --type=github_repo')
    ->everyFifteenMinutes()
    ->name('fetch-github-sources')
    ->withoutOverlapping();

// Check for and dispatch due digest runs every minute
Schedule::command('digests:dispatch-due')
    ->everyMinute()
    ->name('dispatch-due-digests')
    ->withoutOverlapping();
