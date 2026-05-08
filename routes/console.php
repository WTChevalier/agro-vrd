<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Programación de tareas de SazónRD
Schedule::command('sazonrd:recalcular-confianza')->dailyAt('03:00');
Schedule::command('sazonrd:procesar-suscripciones')->dailyAt('06:00');
