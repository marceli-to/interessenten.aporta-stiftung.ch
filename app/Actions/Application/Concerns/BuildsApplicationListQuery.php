<?php

namespace App\Actions\Application\Concerns;

use App\Models\Application;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared query builder for the dashboard application list. Both the paginated
 * list (Application\Get) and the filter-based bulk actions (Application\BulkDelete)
 * build from this, so "select all matching" acts on exactly the rows the list
 * shows — search, status, ranges, district/room pivots and the soft-deleted view
 * all resolve identically on both paths.
 *
 * The `$filters` shape matches GetRequest::filters() (empties already dropped).
 */
trait BuildsApplicationListQuery
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
	 * Apply search + filters to a fresh Application query. Ordering and pagination
	 * are left to the caller (the list paginates; bulk just plucks ids).
	 */
	protected function filteredQuery(?string $search, array $filters): Builder
	{
		return Application::query()
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
			->when($filters['rooms'] ?? null, fn ($query, $slugs) => $this->whereHasAny($query, 'application_rooms', 'room_slug', $slugs));
	}

	/**
	 * Apply the list's ordering to a query: the (whitelisted) sort column with a
	 * stable `id desc` tiebreaker, so list / bulk / browse all agree on order.
	 */
	protected function applyListOrder(Builder $query, string $sort, string $direction): Builder
	{
		$sort = in_array($sort, self::SORTABLE, true) ? $sort : 'opened_at';
		$direction = $direction === 'asc' ? 'asc' : 'desc';

		return $query->orderBy($sort, $direction)->orderBy('id', 'desc');
	}

	/**
	 * Keep applications that have at least one of the given slugs in a preference
	 * pivot (districts / rooms). A correlated EXISTS keeps it to one extra query.
	 */
	protected function whereHasAny($query, string $table, string $column, array $slugs)
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
	protected function applySearch($query, string $search): void
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
