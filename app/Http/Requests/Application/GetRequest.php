<?php

namespace App\Http\Requests\Application;

use App\Enums\IncomeBracket;
use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dashboard list query: search / sort / pagination plus the filter set. Every
 * parameter is optional (the bare list returns everything). The validated input
 * is exposed through small accessors so the controller hands the GetApplications
 * action ready-to-use values and never parses the request itself.
 */
class GetRequest extends FormRequest
{
	/**
	 * Pseudo-status the list filter sends to request soft-deleted applications.
	 * It rides the `status` param but is not a real Status enum case: when present
	 * the list switches to "only trashed" instead of filtering by status value.
	 */
	public const TRASHED = 'deleted';

	public function authorize(): bool
	{
		// Route is behind the `auth` middleware; matches the rest of the dashboard
		// (no per-role policy yet).
		return true;
	}

	public function rules(): array
	{
		return [
			'per_page' => ['sometimes', 'integer'],
			'search' => ['sometimes', 'nullable', 'string'],
			'sort' => ['sometimes', 'string'],
			'direction' => ['sometimes', 'in:asc,desc'],
			'status' => ['sometimes', 'nullable', 'string'],
			'move_in_from' => ['sometimes', 'nullable', 'date'],
			'move_in_to' => ['sometimes', 'nullable', 'date'],
			'rent_min' => ['sometimes', 'nullable', 'numeric'],
			'rent_max' => ['sometimes', 'nullable', 'numeric'],
			'income_min' => ['sometimes', 'nullable', 'string'],
			'income_max' => ['sometimes', 'nullable', 'string'],
			'districts' => ['sometimes', 'nullable', 'string'],
			'rooms' => ['sometimes', 'nullable', 'string'],
		];
	}

	/**
	 * Page size, clamped to a sane range so a hand-crafted query can't ask for an
	 * unbounded page.
	 */
	public function perPage(): int
	{
		return min(max((int) $this->validated('per_page', 25), 1), 100);
	}

	/**
	 * The search term, or null when blank so the action skips searching entirely.
	 */
	public function search(): ?string
	{
		return trim($this->validated('search') ?? '') ?: null;
	}

	public function sort(): string
	{
		return $this->validated('sort', 'opened_at');
	}

	public function direction(): string
	{
		return $this->validated('direction', 'desc');
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
	 * Whether the list should show only soft-deleted applications, signalled by the
	 * `deleted` sentinel in the status param. Returns null (not false) when absent so
	 * the empties-filter in filters() drops it.
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
