<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cleanup of old registration attempts
Schedule::command('registration:cleanup --days=30')->daily()->at('02:00');

// Schedule cache warming
Schedule::command('cache:warm-up')->everyThirtyMinutes();
