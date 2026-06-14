<?php

namespace App\Http\Requests\ApplicationBulk;

use App\Http\Requests\Application\Concerns\ParsesApplicationFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Shared base for bulk operations over a selected set of applications. Mirrors
 * the frontend BulkActionBar's two selection modes:
 *  - explicit: { ids: [...] }
 *  - all-matching: the same filter params the list uses, plus { exclude: [...] }
 *    for rows the user un-ticked.
 *
 * Filter parsing is shared with the list (ParsesApplicationFilters) so a bulk
 * action hits exactly the rows the user saw. A request with neither ids nor any
 * active filter is rejected — bulk actions are always scoped, never "everything".
 *
 * Subclasses override selectionErrorMessage() for the action-specific wording.
 */
abstract class BulkSelectionRequest extends FormRequest
{
	use ParsesApplicationFilters;

	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'ids' => ['sometimes', 'array'],
			'ids.*' => ['integer'],
			'exclude' => ['sometimes', 'array'],
			'exclude.*' => ['integer'],

			// Filter params (all-matching mode) — same names/shape as the list.
			'search' => ['sometimes', 'nullable', 'string'],
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

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator) {
			if (! $this->hasExplicitIds() && $this->search() === null && $this->filters() === []) {
				$validator->errors()->add('ids', $this->selectionErrorMessage());
			}
		});
	}

	/**
	 * The validation message when the request carries no scope (neither ids nor a
	 * filter).
	 */
	abstract protected function selectionErrorMessage(): string;

	/**
	 * Explicit-ids mode is active when a non-empty ids array was sent.
	 */
	public function hasExplicitIds(): bool
	{
		return is_array($this->input('ids')) && $this->input('ids') !== [];
	}

	/**
	 * @return array<int, int>|null  The explicit ids, or null for all-matching mode.
	 */
	public function ids(): ?array
	{
		return $this->hasExplicitIds() ? array_map('intval', $this->validated('ids')) : null;
	}

	/**
	 * @return array<int, int>  Ids to leave out of an all-matching operation.
	 */
	public function exclude(): array
	{
		$exclude = $this->validated('exclude') ?? [];

		return array_map('intval', $exclude);
	}
}
