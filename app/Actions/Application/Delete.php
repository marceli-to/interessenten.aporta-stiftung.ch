<?php

namespace App\Actions\Application;

use App\Models\Application;

class Delete
{
	/**
	 * Soft-delete the application, or permanently destroy it when $force is true.
	 *
	 * Soft delete leaves the entire aggregate (applicants, current_housing,
	 * employers, children, status_events, pivots) intact at the DB level,
	 * so the application can be fully restored later. Force delete triggers
	 * the FK cascade and wipes everything irreversibly.
	 *
	 * Calling soft-delete on an already-trashed application is a no-op — we
	 * don't overwrite the original `deleted_at` timestamp.
	 */
	public function execute(Application $application, bool $force = false): void
	{
		if ($force) {
			$application->forceDelete();

			return;
		}

		if ($application->trashed()) {
			return;
		}

		$application->delete();
	}
}
