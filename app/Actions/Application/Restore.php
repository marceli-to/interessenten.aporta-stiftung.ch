<?php

namespace App\Actions\Application;

use App\Models\Application;

class Restore
{
	/**
	 * Bring a soft-deleted application back into the active list. The entire
	 * aggregate (applicants, current_housing, employers, children, status_events,
	 * pivots) stays on disk during soft-delete (see App\Actions\Application\Delete),
	 * so restore is a single flip of the parent's `deleted_at`.
	 *
	 * Calling restore on an application that is not trashed is a no-op.
	 */
	public function execute(Application $application): void
	{
		if (! $application->trashed()) {
			return;
		}

		$application->restore();
	}
}
