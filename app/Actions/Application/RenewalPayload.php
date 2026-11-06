<?php

namespace App\Actions\Application;

use App\Http\Resources\ApplicationDetailResource;
use App\Models\Application;
use Illuminate\Support\Arr;

class RenewalPayload
{
	public function __construct(
		private Show $show = new Show(),
	) {}

	/**
	 * The application in the intake payload shape (the same sections the public
	 * form submits), so the website can prefill its form for an update. Built
	 * from the detail resource, which already returns raw slugs and Y-m-d dates
	 * in exactly these sections; only the intake sections are passed on —
	 * nothing internal (notes, status trail, property class).
	 *
	 * @return array<string, mixed>
	 */
	public function execute(Application $application): array
	{
		$detail = (new ApplicationDetailResource($this->show->execute($application)))->resolve();

		$payload = Arr::only($detail, [
			'shares_apartment', 'housing_wish', 'household_info',
			'main_applicant', 'co_applicant', 'children',
		]);

		// Rooms are derived from the household size, not part of the form.
		unset($payload['housing_wish']['rooms']);

		return $payload;
	}
}
