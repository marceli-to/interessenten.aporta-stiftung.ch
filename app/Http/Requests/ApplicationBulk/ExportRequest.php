<?php

namespace App\Http\Requests\ApplicationBulk;

/**
 * Synchronous PDF export of a list selection. Shares the selection contract with
 * the other bulk requests and carries the list's sort/direction so the PDF order
 * matches what the user saw. Unlike resolve (read-only), an unscoped request is
 * rejected — an export is always over a deliberate selection, never "everything".
 */
class ExportRequest extends BulkSelectionRequest
{
	public function rules(): array
	{
		return array_merge(parent::rules(), [
			'sort' => ['sometimes', 'string'],
			'direction' => ['sometimes', 'in:asc,desc'],
		]);
	}

	protected function selectionErrorMessage(): string
	{
		return 'PDF-Export benötigt eine Auswahl (ids) oder einen aktiven Filter.';
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
