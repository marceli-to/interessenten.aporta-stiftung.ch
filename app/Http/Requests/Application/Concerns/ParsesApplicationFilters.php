<?php

namespace App\Http\Requests\Application\Concerns;

use App\Enums\IncomeBracket;
use App\Enums\Status;

/**
 * Shared parsing of the dashboard list filter params into the ready-to-use shape
 * the query actions consume. Used by both the list query (GetRequest) and the
 * filter-based bulk actions (ApplicationBulk\DeleteRequest) so "select all
 * matching" resolves to exactly the same filter set the list applies.
 *
 * The consuming request must validate the same param names (status, move_in_*,
 * rent_*, income_*, districts, rooms, search) as nullable strings/numbers.
 */
trait ParsesApplicationFilters
{
	/**
	 * Pseudo-status that requests soft-deleted applications. It rides the `status`
	 * param but is not a real Status enum case: when present the query switches to
	 * "only trashed" instead of filtering by status value.
	 */
	public const TRASHED = 'deleted';

	/**
	 * The search term, or null when blank so the action skips searching entirely.
	 */
	public function search(): ?string
	{
		return trim($this->validated('search') ?? '') ?: null;
	}

	/**
	 * The active filters, with empties dropped so the action only filters on values
	 * the user actually set. Multi-selects arrive as comma-joined lists; everything
	 * else is scalar.
	 */
	public function filters(): array
	{
		return array_filter([
			'status' => $this->splitStatuses($this->validated('status')),
			'trashed' => $this->wantsTrashed(),
			'move_in_from' => $this->validated('move_in_from'),
			'move_in_to' => $this->validated('move_in_to'),
			'rent_min' => $this->validated('rent_min'),
			'rent_max' => $this->validated('rent_max'),
			'income' => $this->incomeBracketSlugs(),
			'districts' => $this->splitSlugs($this->validated('districts')),
			'rooms' => $this->splitSlugs($this->validated('rooms')),
		], fn ($value) => $value !== null && $value !== []);
	}

	/**
	 * Split a comma-joined slug list (e.g. "k4,k5") into a clean array of slugs.
	 */
	private function splitSlugs(?string $value): array
	{
		return $value ? array_values(array_filter(explode(',', $value))) : [];
	}

	/**
	 * Split a comma-joined status list (e.g. "opened,archived") into a clean array,
	 * keeping only values that are real Status enum cases so a bogus query param can
	 * never reach the query. The `deleted` sentinel is intentionally excluded here
	 * (it's handled by wantsTrashed()).
	 */
	private function splitStatuses(?string $value): array
	{
		$valid = array_column(Status::cases(), 'value');

		return array_values(array_intersect($this->splitSlugs($value), $valid));
	}

	/**
	 * Whether the query should target only soft-deleted applications, signalled by
	 * the `deleted` sentinel in the status param. Returns null (not false) when
	 * absent so the empties-filter in filters() drops it.
	 */
	private function wantsTrashed(): ?bool
	{
		return in_array(self::TRASHED, $this->splitSlugs($this->validated('status')), true) ?: null;
	}

	/**
	 * Resolve the income_min / income_max bracket bounds into the concrete set of
	 * bracket slugs the range covers. Returns an empty array when neither bound is
	 * set, so the filter only kicks in once the user picks a range.
	 */
	private function incomeBracketSlugs(): array
	{
		$min = $this->validated('income_min');
		$max = $this->validated('income_max');

		if (($min ?? '') === '' && ($max ?? '') === '') {
			return [];
		}

		return IncomeBracket::slugsInRange($min, $max);
	}
}
