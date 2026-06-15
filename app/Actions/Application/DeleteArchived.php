<?php

namespace App\Actions\Application;

use App\Enums\Status;
use App\Models\Application;
use Carbon\CarbonInterface;

class DeleteArchived
{
	public function __construct(
		private Delete $delete = new Delete(),
	) {}

	/**
	 * Soft-delete archived applications once the retention window has passed.
	 *
	 * Rule (Kundenvorgabe): an archived application is deleted 3 months after it
	 * was archived (archived_at). Deletion is a soft-delete (Application\Delete) —
	 * the row lands in the "Gelöscht" view and stays restorable, mirroring the
	 * back-office bulk delete; no irreversible forceDelete here.
	 *
	 * archived_at is required: rows flagged Archived without a stamped date are
	 * skipped (we can't compute the deadline), never silently deleted. Returns
	 * the number of applications soft-deleted.
	 */
	public function execute(?CarbonInterface $now = null): int
	{
		$now = $now ?? now();
		$cutoff = $now->copy()->subMonths(
			(int) config('aporta.lifecycle.delete_after_months', 3)
		);

		$deleted = 0;

		Application::query()
			->where('status', Status::Archived)
			->whereNotNull('archived_at')
			->where('archived_at', '<=', $cutoff)
			->chunkById(200, function ($applications) use (&$deleted) {
				foreach ($applications as $application) {
					$this->delete->execute($application);
					$deleted++;
				}
			});

		return $deleted;
	}
}
