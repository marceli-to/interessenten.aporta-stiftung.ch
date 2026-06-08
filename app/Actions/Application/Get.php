<?php

namespace App\Actions\Application;

use App\Models\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class Get
{
	/**
	 * Columns the list may be sorted by. Anything outside this whitelist falls
	 * back to the default so user-supplied sort input never reaches orderBy raw.
	 */
	private const SORTABLE = [
		'reference_number',
		'status',
		'opened_at',
		'extended_at',
		'earliest_move_in',
		'max_gross_rent',
		'total_persons',
	];

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
	): LengthAwarePaginator {
		$sort = in_array($sort, self::SORTABLE, true) ? $sort : 'opened_at';
		$direction = $direction === 'asc' ? 'asc' : 'desc';

		$applications = Application::query()
			->with(['mainApplicant.employer'])
			->when($search, $this->applySearch(...))
			->orderBy($sort, $direction)
			->orderBy('id', 'desc')
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
	 * Match the term against the reference number (when numeric) and the main
	 * applicant's name and city.
	 */
	private function applySearch($query, string $search): void
	{
		$query->where(function ($query) use ($search) {
			if (is_numeric($search)) {
				$query->orWhere('reference_number', (int) $search);
			}

			$query->orWhereHas('mainApplicant', function ($applicant) use ($search) {
				$applicant->where('first_name', 'like', "%{$search}%")
					->orWhere('last_name', 'like', "%{$search}%")
					->orWhere('city', 'like', "%{$search}%");
			});
		});
	}
}
