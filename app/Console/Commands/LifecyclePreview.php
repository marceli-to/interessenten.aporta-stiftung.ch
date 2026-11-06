<?php

namespace App\Console\Commands;

use App\Actions\Application\ArchiveStale;
use App\Actions\Application\DeleteArchived;
use App\Enums\Status;
use App\Models\Application;
use Illuminate\Console\Command;

/**
 * Read-only: what the nightly lifecycle jobs (app:archive-stale 03:00,
 * app:delete-archived 03:10) would do on their next run. Changes nothing.
 *
 * Written for switching the prod cron to schedule:run (2026-11): the jobs had
 * never run there, so the first night catches up on the whole backlog.
 */
class LifecyclePreview extends Command
{
	protected $signature = 'app:lifecycle-preview {--list : Also list the applications that would be archived}';

	protected $description = 'Read-only: count what the next archive/delete run would affect.';

	public function handle(ArchiveStale $archiveStale, DeleteArchived $deleteArchived): int
	{
		$toArchive = $archiveStale->candidates();
		$toDelete = $deleteArchived->candidates();

		$this->info('Nächster Lauf (nichts wird verändert):');
		$this->table(['', 'Anzahl'], [
			['Aktiv heute (Eröffnet + Verlängert)', Application::whereIn('status', [Status::Opened, Status::Extended])->count()],
			['→ würden archiviert (03:00)', (clone $toArchive)->count()],
			['   davon Eröffnet', (clone $toArchive)->where('status', Status::Opened)->count()],
			['   davon Verlängert', (clone $toArchive)->where('status', Status::Extended)->count()],
			['Archiviert heute', Application::where('status', Status::Archived)->count()],
			['→ würden nach „Gelöscht" verschoben (03:10)', (clone $toDelete)->count()],
			['   archiviert ohne Datum (werden nie gelöscht)', Application::where('status', Status::Archived)->whereNull('archived_at')->count()],
		]);

		// When the archived rows that stay tonight would be deleted later: the
		// month their 3-month window runs out.
		$months = (int) config('aporta.lifecycle.delete_after_months', 3);
		$later = Application::where('status', Status::Archived)
			->whereNotNull('archived_at')
			->whereNotIn('id', (clone $toDelete)->select('id'))
			->get(['archived_at'])
			->groupBy(fn ($a) => $a->archived_at->copy()->addMonths($months)->format('Y-m'))
			->sortKeys()
			->map(fn ($group, $month) => [$month, $group->count()])
			->values();

		if ($later->isNotEmpty()) {
			$this->newLine();
			$this->info('Bereits Archivierte, die später gelöscht würden (nach Monat):');
			$this->table(['Monat', 'Anzahl'], $later->all());
		}

		if ($this->option('list')) {
			$rows = (clone $toArchive)
				->with('mainApplicant:id,application_id,first_name,last_name')
				->get()
				->sortBy(fn (Application $a) => $a->renewalReferenceDate())
				->map(fn (Application $a) => [
					$a->reference_number,
					trim($a->mainApplicant?->last_name.' '.$a->mainApplicant?->first_name),
					$a->status->label(),
					$a->opened_at->format('d.m.Y'),
					$a->extended_at?->format('d.m.Y') ?? '–',
					$a->validUntil()->format('d.m.Y'),
				]);

			$this->newLine();
			$this->info('Würden archiviert:');
			$this->table(['Nr.', 'Name', 'Status', 'Angemeldet', 'Verlängert am', 'Gültig bis'], $rows->all());
		}

		$this->newLine();
		$this->line('Hinweis: Wer heute Nacht archiviert wird, wird erst 3 Monate später gelöscht. Löschen = Soft-Delete (wiederherstellbar unter „Gelöscht").');

		return self::SUCCESS;
	}
}
