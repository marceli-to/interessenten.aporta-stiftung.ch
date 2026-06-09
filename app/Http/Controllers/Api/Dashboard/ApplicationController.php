<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\Application\Get as GetApplications;
use App\Actions\Application\Show as ShowApplication;
use App\Actions\Application\Update as UpdateApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\UpdateRequest;
use App\Http\Resources\ApplicationDetailResource;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
	public function index(Request $request)
	{
		$perPage = min(max($request->integer('per_page', 25), 1), 100);
		$search = $request->string('search')->trim()->value() ?: null;
		$sort = $request->string('sort')->value() ?: 'opened_at';
		$direction = $request->string('direction')->value() ?: 'desc';

		return ApplicationResource::collection(
			(new GetApplications())->execute($perPage, $search, $sort, $direction)
		);
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
}
