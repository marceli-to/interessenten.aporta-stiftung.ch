<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\Application\Show as ShowApplication;
use App\Actions\Status\Transition as TransitionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationStatus\UpdateRequest;
use App\Http\Resources\ApplicationDetailResource;
use App\Models\Application;

class ApplicationStatusController extends Controller
{
	/**
	 * Status transitions go through the dedicated Status\Transition action (not the
	 * generic Application Update), which flips the status, writes an audit
	 * StatusEvent attributed to the acting user, and stamps the transition date.
	 */
	public function update(UpdateRequest $request, Application $application)
	{
		(new TransitionStatus())->execute(
			application: $application,
			to: $request->status(),
			actorUserId: $request->user()?->id,
			reason: $request->reason(),
			transitionDate: $request->transitionDate(),
		);

		return new ApplicationDetailResource(
			(new ShowApplication())->execute($application)
		);
	}
}
