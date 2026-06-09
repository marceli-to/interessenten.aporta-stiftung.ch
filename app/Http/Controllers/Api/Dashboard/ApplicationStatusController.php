<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\Application\Show as ShowApplication;
use App\Actions\Status\Record as RecordStatus;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationDetailResource;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationStatusController extends Controller
{
	/**
	 * Status transitions go through the dedicated Status\Record action (not the
	 * generic Application Update), which flips the status and writes an audit
	 * StatusEvent attributed to the acting user.
	 */
	public function update(Request $request, Application $application)
	{
		$validated = $request->validate([
			'status' => ['required', Rule::enum(Status::class)],
			'reason' => ['nullable', 'string', 'max:255'],
			'extended_at' => ['nullable', 'date'],
			'archived_at' => ['nullable', 'date'],
		]);

		$to = Status::from($validated['status']);

		(new RecordStatus())->execute(
			application: $application,
			to: $to,
			actorUserId: $request->user()?->id,
			reason: $validated['reason'] ?? null,
		);

		// Stamp the transition date for the target state when the client supplies
		// it (the Info panel reveals a date field for Verlängert / Archiviert).
		if ($to === Status::Extended && array_key_exists('extended_at', $validated)) {
			$application->extended_at = $validated['extended_at'];
			$application->save();
		}
		if ($to === Status::Archived && array_key_exists('archived_at', $validated)) {
			$application->archived_at = $validated['archived_at'];
			$application->save();
		}

		return new ApplicationDetailResource(
			(new ShowApplication())->execute($application)
		);
	}
}
