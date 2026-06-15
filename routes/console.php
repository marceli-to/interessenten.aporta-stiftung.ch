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

/*
 * Lifecycle-Automatisierung (Todo §6):
 *  - Auto-Archivieren: offene/verlängerte Bewerbungen 6 + 3 Monate (Kulanz)
 *    nach dem Stichtag (extended_at bzw. opened_at) → Status „Archiviert".
 *  - Auto-Löschen: archivierte Bewerbungen 3 Monate nach archived_at →
 *    Soft-Delete (landet im „Gelöscht"-Papierkorb, wiederherstellbar).
 * Archivieren läuft zuerst, damit eine Bewerbung erst nach Ablauf ihrer
 * eigenen 3-Monats-Frist ab archived_at gelöscht wird.
 */
Schedule::command('app:archive-stale')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('app:delete-archived')->dailyAt('03:10')->withoutOverlapping();
