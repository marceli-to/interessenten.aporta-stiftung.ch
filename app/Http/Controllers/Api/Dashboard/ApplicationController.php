<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\Application\Delete as DeleteApplication;
use App\Actions\Application\Get as GetApplications;
use App\Actions\Application\Show as ShowApplication;
use App\Actions\Application\Update as UpdateApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\GetRequest;
use App\Http\Requests\Application\UpdateRequest;
use App\Http\Resources\ApplicationDetailResource;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;

class ApplicationController extends Controller
{
	public function index(GetRequest $request)
	{
		$action = new GetApplications();

		return ApplicationResource::collection(
			$action->execute(
				$request->perPage(),
				$request->search(),
				$request->sort(),
				$request->direction(),
				$request->filters(),
			)
		)->additional([
			'status_counts' => $action->statusCounts(),
		]);
	}

	public function show(Application $application)
	{
		return new ApplicationDetailResource(
			(new ShowApplication())->execute($application)
		);
	}

	public function update(UpdateRequest $request, Application $application)
	{
		$application = app(UpdateApplication::class)->execute($application, $request->validated());

		return new ApplicationDetailResource(
			(new ShowApplication())->execute($application)
		);
	}

	public function destroy(Application $application)
	{
		(new DeleteApplication())->execute($application);

		return response()->json(null, 204);
	}
}
