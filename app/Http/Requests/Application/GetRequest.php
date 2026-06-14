<?php

namespace App\Http\Requests\Application;

use App\Http\Requests\Application\Concerns\ParsesApplicationFilters;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dashboard list query: search / sort / pagination plus the filter set. Every
 * parameter is optional (the bare list returns everything). The validated input
 * is exposed through small accessors so the controller hands the GetApplications
 * action ready-to-use values and never parses the request itself.
 *
 * The filter parsing (search / filters and the `deleted` sentinel) lives in the
 * shared ParsesApplicationFilters trait, so the bulk actions resolve "all
 * matching" through the exact same logic.
 */
class GetRequest extends FormRequest
{
	use ParsesApplicationFilters;

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

	public function sort(): string
	{
		return $this->validated('sort', 'opened_at');
	}

	public function direction(): string
	{
		return $this->validated('direction', 'desc');
	}
}
