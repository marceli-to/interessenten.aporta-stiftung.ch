<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\Application\Delete as DeleteApplication;
use App\Actions\Application\Get as GetApplications;
use App\Actions\Application\Show as ShowApplication;
use App\Actions\Application\Update as UpdateApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\UpdateRequest;
use App\Enums\Status;
use App\Http\Resources\ApplicationDetailResource;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
	public function index(Request $request)
	{
		$validated = $request->validate([
			'per_page' => ['sometimes', 'integer'],
			'search' => ['sometimes', 'nullable', 'string'],
			'sort' => ['sometimes', 'string'],
			'direction' => ['sometimes', 'in:asc,desc'],
			'status' => ['sometimes', 'nullable', Rule::enum(Status::class)],
			'move_in_from' => ['sometimes', 'nullable', 'date'],
			'move_in_to' => ['sometimes', 'nullable', 'date'],
			'rent_min' => ['sometimes', 'nullable', 'numeric'],
			'rent_max' => ['sometimes', 'nullable', 'numeric'],
			'districts' => ['sometimes', 'nullable', 'string'],
			'rooms' => ['sometimes', 'nullable', 'string'],
		]);

		$perPage = min(max((int) ($validated['per_page'] ?? 25), 1), 100);
		$search = trim($validated['search'] ?? '') ?: null;
		$sort = $validated['sort'] ?? 'opened_at';
		$direction = $validated['direction'] ?? 'desc';

		// Multi-selects arrive as comma-joined slug lists; everything else is scalar.
		// Drop empties so the action only filters on values the user actually set.
		$filters = array_filter([
			'status' => $validated['status'] ?? null,
			'move_in_from' => $validated['move_in_from'] ?? null,
			'move_in_to' => $validated['move_in_to'] ?? null,
			'rent_min' => $validated['rent_min'] ?? null,
			'rent_max' => $validated['rent_max'] ?? null,
			'districts' => $this->splitSlugs($validated['districts'] ?? null),
			'rooms' => $this->splitSlugs($validated['rooms'] ?? null),
		], fn ($value) => $value !== null && $value !== []);

		$action = new GetApplications();

		return ApplicationResource::collection(
			$action->execute($perPage, $search, $sort, $direction, $filters)
		)->additional([
			'status_counts' => $action->statusCounts(),
		]);
	}

	/**
	 * Split a comma-joined slug list (e.g. "k4,k5") into a clean array of slugs.
	 */
	private function splitSlugs(?string $value): array
	{
		return $value ? array_values(array_filter(explode(',', $value))) : [];
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
