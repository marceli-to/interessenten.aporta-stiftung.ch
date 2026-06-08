<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Application\Store as StoreApplication;
use App\Http\Requests\Application\StoreRequest;
use Illuminate\Http\JsonResponse;

class ApplicationStoreController
{
	public function store(StoreRequest $request): JsonResponse
	{
		$application = (new StoreApplication())->execute($request->validated());

		$payload = [
			'data' => [
				'reference_number' => $application->reference_number,
				'status' => $application->status->value,
				'opened_at' => $application->opened_at->toIso8601String(),
			],
		];

		return response()->json($payload, $application->wasRecentlyCreated ? 201 : 200);
	}
}
