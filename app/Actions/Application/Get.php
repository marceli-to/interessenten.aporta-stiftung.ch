<?php

namespace App\Actions\Application;

use App\Actions\Application\Concerns\BuildsApplicationListQuery;
use App\Models\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class Get
{
	use BuildsApplicationListQuery;

	/**
	 * Paginated list of applications for the dashboard, each enriched with its
	 * room/district slug lists. The pivots are fetched in two batched queries
	 * (keyed by application id) rather than one query per row.
	 *
	 * Search and sort are applied to the query before paginating, so they cover
	 * the whole dataset rather than just the current page.
	 */
	public function execute(
		int $perPage = 25,
		?string $search = null,
		string $sort = 'opened_at',
		string $direction = 'desc',
		array $filters = [],
	): LengthAwarePaginator {
		$query = $this->filteredQuery($search, $filters)->with(['mainApplicant.employer']);

		$applications = $this->applyListOrder($query, $sort, $direction)
			->paginate($perPage)
			->withQueryString();

		$ids = $applications->pluck('id');

		$rooms = DB::table('application_rooms')
			->whereIn('application_id', $ids)
			->get()
			->groupBy('application_id');

		$districts = DB::table('application_districts')
			->whereIn('application_id', $ids)
			->get()
			->groupBy('application_id');

		$applications->each(function (Application $application) use ($rooms, $districts) {
			$application->room_slugs = $rooms->get($application->id, collect())->pluck('room_slug')->all();
			$application->district_slugs = $districts->get($application->id, collect())->pluck('district_slug')->all();
		});

		return $applications;
	}

	/**
	 * Total applications per status, keyed by status value, for the filter's
	 * status badges. Ignores the active filters so each badge always shows the
	 * full size of its bucket. A `deleted` key carries the soft-deleted total so
	 * the "Gelöscht" filter button can show its count alongside the others.
	 */
	public function statusCounts(): array
	{
		$counts = Application::query()
			->selectRaw('status, count(*) as total')
			->groupBy('status')
			->pluck('total', 'status')
			->all();

		$counts['deleted'] = Application::onlyTrashed()->count();

		return $counts;
	}
}
