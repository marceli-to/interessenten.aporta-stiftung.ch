<?php

namespace App\Http\Requests\ApplicationBulk;

/**
 * Bulk restore of soft-deleted applications from the "Gelöscht" list. See
 * BulkSelectionRequest for the shared selection contract (ids or filter +
 * exclude). In the trashed view the filter carries `status=deleted`, which
 * resolves to onlyTrashed() through the shared query trait.
 */
class RestoreRequest extends BulkSelectionRequest
{
	protected function selectionErrorMessage(): string
	{
		return 'Bulk-Wiederherstellung benötigt eine Auswahl (ids) oder einen aktiven Filter.';
	}
}
