<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\Application\BulkDelete as BulkDeleteApplications;
use App\Actions\Application\BulkRestore as BulkRestoreApplications;
use App\Actions\Application\Pdf\Generate as GeneratePdf;
use App\Actions\Application\ResolveIds as ResolveApplicationIds;
use App\Actions\Application\Show as ShowApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationBulk\DeleteRequest;
use App\Http\Requests\ApplicationBulk\ExportRequest;
use App\Http\Requests\ApplicationBulk\ResolveRequest;
use App\Http\Requests\ApplicationBulk\RestoreRequest;

/**
 * Bulk operations over a set of applications selected from the dashboard list.
 * The selection arrives in one of two shapes (see DeleteRequest): explicit
 * `ids`, or the active filter params plus `exclude` for "all matching". Both are
 * resolved server-side through the shared filter parsing, so a bulk action hits
 * exactly the rows the user saw.
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

	public function restore(RestoreRequest $request)
	{
		$restored = (new BulkRestoreApplications())->execute(
			ids: $request->ids(),
			search: $request->search(),
			filters: $request->filters(),
			exclude: $request->exclude(),
		);

		return response()->json(['restored' => $restored]);
	}

	/**
	 * Resolve a selection to the ordered id list that seeds the browse set
	 * (Resultatansicht). Read-only.
	 */
	public function resolve(ResolveRequest $request)
	{
		$ids = (new ResolveApplicationIds())->execute(
			ids: $request->ids(),
			search: $request->search(),
			sort: $request->sort(),
			direction: $request->direction(),
			filters: $request->filters(),
			exclude: $request->exclude(),
		);

		return response()->json(['ids' => $ids]);
	}

	/**
	 * Render the selection to a single PDF and stream it back as a download,
	 * synchronously. Capped at aporta.exports.max_sync so a request never renders
	 * long enough to risk a timeout. Same selection resolution as the other bulk
	 * actions, in list order so the PDF matches what the user saw.
	 */
	public function export(ExportRequest $request)
	{
		$ids = (new ResolveApplicationIds())->execute(
			ids: $request->ids(),
			search: $request->search(),
			sort: $request->sort(),
			direction: $request->direction(),
			filters: $request->filters(),
			exclude: $request->exclude(),
		);

		if ($ids === []) {
			return response()->json(['message' => 'Die Auswahl enthält keine Bewerbungen.'], 422);
		}

		$count = count($ids);
		$max = (int) config('aporta.exports.max_sync', 100);
		if ($count > $max) {
			return response()->json([
				'message' => "Die Auswahl umfasst {$count} Bewerbungen. Bitte den Filter auf höchstens {$max} eingrenzen.",
			], 422);
		}

		$applications = (new ShowApplication())->loadMany($ids);

		$filename = $applications->count() === 1
			? 'Wohnungsinteressent_'.$applications->first()->reference_number.'.pdf'
			: 'Wohnungsinteressenten_'.now()->format('Y-m-d').'.pdf';

		return (new GeneratePdf())->download($applications, $filename);
	}
}
