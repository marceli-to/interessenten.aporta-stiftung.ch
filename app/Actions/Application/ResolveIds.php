<?php

namespace App\Actions\Application;

use App\Actions\Application\Concerns\BuildsApplicationListQuery;
use App\Models\Application;

class ResolveIds
{
	use BuildsApplicationListQuery;

	/**
	 * Resolve a list selection to an ordered array of application ids, in the same
	 * order the list shows them (sort + id tiebreaker). Used to seed the browse
	 * set so "Öffnen" can step prev/next through exactly the chosen rows.
	 *
	 * Two selection modes (mirroring the BulkActionBar):
	 *  - explicit ids: order the given ids by the list ordering.
	 *  - all-matching: resolve from the same filtered query, minus `exclude`.
	 *
	 * @return array<int, int>
	 */
	public function execute(
		?array $ids = null,
		?string $search = null,
		string $sort = 'opened_at',
		string $direction = 'desc',
		array $filters = [],
		array $exclude = [],
	): array {
		// An unscoped resolve (no ids, no search, no filter) is not "everything" —
		// browse is always over a deliberate selection, so resolve to nothing.
		if ($ids === null && $search === null && $filters === []) {
			return [];
		}

		$query = $ids !== null
			// Trashed rows can be in an explicit selection from the "Gelöscht" view,
			// so include them; the filter path scopes via `trashed` in the trait.
			? Application::withTrashed()->whereIn('id', $ids)
			: $this->filteredQuery($search, $filters);

		if ($exclude !== []) {
			$query->whereNotIn('id', $exclude);
		}

		return $this->applyListOrder($query, $sort, $direction)
			->pluck('id')
			->all();
	}
}
