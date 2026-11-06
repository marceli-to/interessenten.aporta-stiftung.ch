<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Application\Renew;
use App\Actions\Application\RenewalPayload;
use App\Http\Requests\Application\RenewRequest;
use App\Support\RenewalToken;
use Illuminate\Http\JsonResponse;

/**
 * Server-to-server endpoints for the prefilled form on the website (renewal
 * mode). The website authenticates with the intake API key; the renewal token
 * from the renewal link email decides which application — and only that one — can
 * be read and updated.
 */
class RenewalController
{
	public function show(string $token, RenewalPayload $payload): JsonResponse
	{
		$application = RenewalToken::resolve($token);
		if (! $application) {
			return $this->invalid();
		}

		return response()->json([
			'data' => $payload->execute($application),
			'meta' => [
				'reference_number' => $application->reference_number,
				'valid_until' => $application->validUntil()->toDateString(),
			],
		]);
	}

	public function update(string $token, RenewRequest $request, Renew $renew): JsonResponse
	{
		$application = RenewalToken::resolve($token);
		if (! $application) {
			return $this->invalid();
		}

		// No co_applicant in the payload means the household has none (any more).
		$data = $request->validated() + ['co_applicant' => null];

		$application = $renew->execute($application, $data);

		return response()->json([
			'data' => [
				'reference_number' => $application->reference_number,
				'valid_until' => $application->validUntil()->toDateString(),
			],
		]);
	}

	private function invalid(): JsonResponse
	{
		return response()->json(['message' => 'Renewal link is invalid or expired.'], 410);
	}
}
