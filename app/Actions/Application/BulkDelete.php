<?php

namespace App\Actions\Application;

use App\Actions\Application\Concerns\BuildsApplicationListQuery;
use App\Models\Application;

class BulkDelete
{
	use BuildsApplicationListQuery;

	public function __construct(
		private Delete $delete = new Delete(),
	) {}

	/**
	 * Soft-delete a set of applications selected from the dashboard list.
	 *
	 * Two selection modes (mirroring the frontend BulkActionBar):
	 *  - explicit ids: delete exactly those.
	 *  - all-matching: resolve the ids from the same filtered query the list uses
	 *    (BuildsApplicationListQuery), minus any the user un-ticked (`exclude`).
	 *
	 * Each row goes through Application\Delete so the behaviour is identical to a
	 * single delete (soft-delete, activity log, no-op on already-trashed rows).
	 * Returns the number of rows actually soft-deleted.
	 */
	public function execute(
		?array $ids = null,
		?string $search = null,
		array $filters = [],
		array $exclude = [],
	): int {
		$query = $ids !== null
			? Application::query()->whereIn('id', $ids)
			: $this->filteredQuery($search, $filters);

		if ($exclude !== []) {
			$query->whereNotIn('id', $exclude);
		}

		$deleted = 0;

		$query->chunkById(200, function ($applications) use (&$deleted) {
			foreach ($applications as $application) {
				if ($application->trashed()) {
					continue;
				}
				$this->delete->execute($application);
				$deleted++;
			}
		});

		return $deleted;
	}
}
