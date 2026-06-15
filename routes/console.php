<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Cron-only Worker-Modell (Prod hat keinen dauerhaften Worker-Prozess):
 * Der Server-Crontab ruft jede Minute `php artisan schedule:run` auf. Der
 * Scheduler startet hier einen kurzlebigen Worker, der die Datenbank-Queue
 * leert und sich beendet (`--stop-when-empty`) – kein Daemon nötig.
 *
 * Crontab-Eintrag auf dem Prod-Server:
 *   * * * * * cd /pfad/zur/app && php artisan schedule:run >> /dev/null 2>&1
 *
 * Hinweis Prod: Der per Cron gestartete Worker rendert PDFs via `->onLambda()`
 * (Sidecar/Browsershot) und braucht daher AWS-Credentials in seiner Umgebung
 * (gleiche Env wie der Web-Prozess).
 */
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
