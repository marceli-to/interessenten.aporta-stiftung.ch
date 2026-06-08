<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\Application\Get as GetApplications;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
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
}
