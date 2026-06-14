<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\Application\BulkDelete as BulkDeleteApplications;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationBulk\DeleteRequest;

/**
 * Bulk operations over a set of applications selected from the dashboard list.
 * The selection arrives in one of two shapes (see DeleteRequest): explicit
 * `ids`, or the active filter params plus `exclude` for "all matching". Both are
 * resolved server-side through the shared filter parsing, so a bulk action hits
 * exactly the rows the user saw.
 *
 * Export will join here as a second method once the field set is confirmed (§4).
 */
class ApplicationBulkController extends Controller
{
	public function destroy(DeleteRequest $request)
	{
		$deleted = (new BulkDeleteApplications())->execute(
			ids: $request->ids(),
			search: $request->search(),
			filters: $request->filters(),
			exclude: $request->exclude(),
		);

		return response()->json(['deleted' => $deleted]);
	}
}
