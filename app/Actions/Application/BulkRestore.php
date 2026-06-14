<?php

namespace App\Actions\Application;

use App\Actions\Application\Concerns\BuildsApplicationListQuery;
use App\Models\Application;

class BulkRestore
{
	use BuildsApplicationListQuery;

	public function __construct(
		private Restore $restore = new Restore(),
	) {}

	/**
	 * Restore a set of soft-deleted applications selected from the "Gelöscht" list.
	 *
	 * Two selection modes (mirroring the frontend BulkActionBar):
	 *  - explicit ids: restore exactly those. The query is scoped to onlyTrashed()
	 *    because the default scope hides soft-deleted rows.
	 *  - all-matching: resolve from the same filtered query the list uses. In the
	 *    trashed view the filter carries `trashed`, so filteredQuery() already
	 *    scopes to onlyTrashed(); `exclude` drops the un-ticked rows.
	 *
	 * Each row goes through Application\Restore (no-op on rows that aren't trashed).
	 * Returns the number of rows actually restored.
	 */
	public function execute(
		?array $ids = null,
		?string $search = null,
		array $filters = [],
		array $exclude = [],
	): int {
		$query = $ids !== null
			? Application::onlyTrashed()->whereIn('id', $ids)
			: $this->filteredQuery($search, $filters);

		if ($exclude !== []) {
			$query->whereNotIn('id', $exclude);
		}

		$restored = 0;

		$query->chunkById(200, function ($applications) use (&$restored) {
			foreach ($applications as $application) {
				if (! $application->trashed()) {
					continue;
				}
				$this->restore->execute($application);
				$restored++;
			}
		});

		return $restored;
	}
}
