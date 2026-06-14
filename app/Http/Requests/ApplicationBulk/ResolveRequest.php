<?php

namespace App\Http\Requests\ApplicationBulk;

/**
 * Resolve a list selection to an ordered id array (for the browse / Resultatansicht
 * feature). Shares the selection contract with the other bulk requests but also
 * carries the list's sort/direction so the resulting order matches what the user
 * saw. Read-only, so an empty selection is allowed (it simply resolves to []).
 */
class ResolveRequest extends BulkSelectionRequest
{
	public function rules(): array
	{
		return array_merge(parent::rules(), [
			'sort' => ['sometimes', 'string'],
			'direction' => ['sometimes', 'in:asc,desc'],
		]);
	}

	/**
	 * Resolving is read-only, so — unlike delete/restore — an unscoped request is
	 * harmless (it yields an empty list). Skip the "needs a scope" guard.
	 */
	public function withValidator($validator): void
	{
		//
	}

	protected function selectionErrorMessage(): string
	{
		return '';
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
