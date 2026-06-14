<?php

namespace App\Http\Requests\ApplicationBulk;

/**
 * Bulk soft-delete from the dashboard list. See BulkSelectionRequest for the
 * shared selection contract (ids or filter + exclude).
 */
class DeleteRequest extends BulkSelectionRequest
{
	protected function selectionErrorMessage(): string
	{
		return 'Bulk-Löschung benötigt eine Auswahl (ids) oder einen aktiven Filter.';
	}
}
