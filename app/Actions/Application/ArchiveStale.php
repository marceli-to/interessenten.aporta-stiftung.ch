<?php

namespace App\Actions\Application;

use App\Actions\Status\Transition;
use App\Enums\Status;
use App\Models\Application;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class ArchiveStale
{
	public function __construct(
		private Transition $transition = new Transition(),
	) {}

	/**
	 * Auto-archive applications whose intake has gone stale.
	 *
	 * Rule (Kundenvorgabe): 6 months validity + 3 months grace ("Kulanz") after
	 * the reference date. The reference date is extended_at when the application
	 * was renewed ("Verlängert"), otherwise opened_at. Only Opened/Extended
	 * applications are candidates — Archived and KNIF are terminal here.
	 *
	 * Each transition goes through Status\Transition so it leaves the same audit
	 * StatusEvent trail as a back-office archive (actor stays null = system) and
	 * stamps archived_at. Returns the number of applications archived.
	 */
	public function execute(?CarbonInterface $now = null): int
	{
		$now = $now ?? now();
		$archived = 0;

		$this->candidates($now)
			->chunkById(200, function ($applications) use (&$archived, $now) {
				foreach ($applications as $application) {
					$this->transition->execute(
						application: $application,
						to: Status::Archived,
						reason: 'Automatisch archiviert (Frist abgelaufen)',
						transitionDate: ['archived_at' => $now],
					);
					$archived++;
				}
			});

		return $archived;
	}

	/**
	 * The applications a run at $now would archive (also used by the
	 * read-only app:lifecycle-preview).
	 */
	public function candidates(?CarbonInterface $now = null): Builder
	{
		$cutoff = ($now ?? now())->copy()->subMonths(
			(int) config('aporta.lifecycle.archive_after_months', 9)
		);

		return Application::query()
			->whereIn('status', [Status::Opened, Status::Extended])
			->whereRaw('COALESCE(extended_at, opened_at) <= ?', [$cutoff]);
	}
}
