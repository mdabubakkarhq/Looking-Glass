<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| Schedule recurring tasks for the Looking Glass platform.
|
*/

// Refresh node latency cache every 15 seconds
Schedule::command('lg:refresh-latency')->everyFifteenSeconds();

// Check for stale nodes every minute
Schedule::command('lg:check-nodes')->everyMinute();

// Clean up old rate limit events daily
Schedule::command('lg:cleanup-rate-limits')->daily();

// Clean up old security events based on retention policy
Schedule::command('lg:cleanup-security-events')->daily();

// Clean up old test events weekly
Schedule::command('lg:cleanup-tests')->weekly();

// Purge old completed/failed tests and their events daily
Schedule::command('lg:purge-tests')->daily();
