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
		array $filters = [],
	): LengthAwarePaginator {
		$sort = in_array($sort, self::SORTABLE, true) ? $sort : 'opened_at';
		$direction = $direction === 'asc' ? 'asc' : 'desc';

		$applications = Application::query()
			->with(['mainApplicant.employer'])
			// "Gelöscht" is a view onto the soft-deleted rows, which the default
			// global scope hides. It's exclusive of the status filter (the UI clears
			// one when the other is picked).
			->when($filters['trashed'] ?? null, fn ($query) => $query->onlyTrashed())
			->when($search, $this->applySearch(...))
			->when($filters['status'] ?? null, fn ($query, $statuses) => $query->whereIn('status', $statuses))
			->when($filters['move_in_from'] ?? null, fn ($query, $date) => $query->whereDate('earliest_move_in', '>=', $date))
			->when($filters['move_in_to'] ?? null, fn ($query, $date) => $query->whereDate('earliest_move_in', '<=', $date))
			->when($filters['rent_min'] ?? null, fn ($query, $value) => $query->where('max_gross_rent', '>=', $value))
			->when($filters['rent_max'] ?? null, fn ($query, $value) => $query->where('max_gross_rent', '<=', $value))
			->when($filters['income'] ?? null, fn ($query, $slugs) => $query->whereHas('mainApplicant.employer', fn ($employer) => $employer->whereIn('annual_income_bracket_slug', $slugs)))
			->when($filters['districts'] ?? null, fn ($query, $slugs) => $this->whereHasAny($query, 'application_districts', 'district_slug', $slugs))
			->when($filters['rooms'] ?? null, fn ($query, $slugs) => $this->whereHasAny($query, 'application_rooms', 'room_slug', $slugs))
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

	/**
	 * Keep applications that have at least one of the given slugs in a preference
	 * pivot (districts / rooms). A correlated EXISTS keeps it to one extra query.
	 */
	private function whereHasAny($query, string $table, string $column, array $slugs)
	{
		return $query->whereExists(function ($sub) use ($table, $column, $slugs) {
			$sub->selectRaw('1')
				->from($table)
				->whereColumn("{$table}.application_id", 'applications.id')
				->whereIn($column, $slugs);
		});
	}

	/**
	 * Match the term against the reference number and person count (when numeric),
	 * the main applicant's name and city, and the text of any attached note.
	 */
	private function applySearch($query, string $search): void
	{
		$query->where(function ($query) use ($search) {
			if (is_numeric($search)) {
				$query->orWhere('reference_number', (int) $search);
				$query->orWhere('total_persons', (int) $search);
			}

			$query->orWhereHas('mainApplicant', function ($applicant) use ($search) {
				$applicant->where('first_name', 'like', "%{$search}%")
					->orWhere('last_name', 'like', "%{$search}%")
					->orWhere('city', 'like', "%{$search}%");
			});

			$query->orWhereHas('notes', function ($note) use ($search) {
				$note->where('body', 'like', "%{$search}%");
			});
		});
	}
}
