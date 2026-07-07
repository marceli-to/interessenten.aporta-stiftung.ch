<?php

namespace App\Http\Resources;

use App\Enums\District;
use App\Enums\LabeledEnum;
use App\Enums\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$applicant = $this->mainApplicant;
		$employer = $applicant?->employer;

		return [
			'id' => $this->id,
			'reference_number' => $this->reference_number,
			'status' => [
				'value' => $this->status->value,
				'label' => $this->status->label(),
			],
			'remarks' => $this->remarks,
			'opened_at' => $this->opened_at?->toIso8601String(),
			'extended_at' => $this->extended_at?->toIso8601String(),
			'earliest_move_in' => $this->earliest_move_in?->toIso8601String(),
			'max_gross_rent' => $this->max_gross_rent,
			'total_persons' => $this->total_persons,
			'rooms' => $this->labels($this->room_slugs ?? [], Room::class),
			'districts' => collect($this->district_slugs ?? [])
				->map(fn (string $slug) => District::from($slug))
				->sortBy(fn (District $d) => $d->sortOrder())
				->map(fn (District $d) => str_replace('Kreis ', 'K', $d->label()))
				->values()
				->all(),
			'main_applicant' => $applicant ? [
				'salutation' => $applicant->salutation?->label(),
				'first_name' => $applicant->first_name,
				'last_name' => $applicant->last_name,
				'street' => trim("{$applicant->street} {$applicant->street_number}"),
				'postal_code' => $applicant->postal_code,
				'city' => $applicant->city,
				'workload_percent' => $employer?->workload_percent,
				'income_bracket' => $employer?->annual_income_bracket_slug?->shortLabel(),
			] : null,
		];
	}

	/**
	 * Map a list of enum slugs to their sorted labels.
	 *
	 * @param  array<int, string>  $slugs
	 * @param  class-string<LabeledEnum>  $enum
	 * @return array<int, string>
	 */
	private function labels(array $slugs, string $enum): array
	{
		return collect($slugs)
			->map(fn (string $slug) => $enum::from($slug))
			->sortBy(fn (LabeledEnum $case) => $case->sortOrder())
			->map(fn (LabeledEnum $case) => $case->label())
			->values()
			->all();
	}
}
