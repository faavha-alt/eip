<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// wa-blast sinkron pegawai->kontak jam 03:15; beri jeda sebelum tarik balik nomor.
Schedule::command('pegawai:pull-nomor-wa')->dailyAt('04:00')->withoutOverlapping();
