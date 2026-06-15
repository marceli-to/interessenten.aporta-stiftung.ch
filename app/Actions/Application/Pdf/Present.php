<?php

namespace App\Actions\Application\Pdf;

use App\Enums\District;
use App\Enums\Floor;
use App\Enums\Room;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\CurrentHousing;
use Carbon\CarbonInterface;

/**
 * Turn a fully-loaded Application model into the resolved, display-ready array
 * the PDF Blade template ($a) expects: enum values become labels, dates become
 * Swiss d.m.Y strings, amounts/phones are formatted exactly like the SPA detail
 * panels. Unlike ApplicationDetailResource (which keeps RAW values for SPA form
 * binding), this is presentation-only, so the template stays free of
 * formatting/enum logic.
 *
 * Expects the relations from Application\Show::relations() plus the
 * district/floor/room preference slugs attached by Show.
 */
class Present
{
	/**
	 * @return array<string, mixed>
	 */
	public function execute(Application $application): array
	{
		$people = collect([$application->mainApplicant])
			->merge($application->coApplicants)
			->filter()
			->values();

		return [
			'reference_number' => $application->reference_number,
			'status' => $application->status->label(),
			'opened_at' => $this->date($application->opened_at),
			'last_changed_at' => $this->date($application->last_changed_at),
			'shares_apartment' => (bool) $application->shares_apartment,

			'housing_wish' => [
				'max_gross_rent' => $this->money($application->max_gross_rent),
				'earliest_move_in' => $this->date($application->earliest_move_in),
				'wants_balcony' => $application->wants_balcony,
				'wants_elevator' => $application->wants_elevator,
				'districts' => $this->labels(District::class, $application->district_slugs ?? []),
				'floors' => $this->labels(Floor::class, $application->floor_slugs ?? []),
				'rooms' => $this->labels(Room::class, $application->room_slugs ?? []),
			],

			'household_info' => [
				'total_persons' => $application->total_persons,
				'adults_count' => $application->adults_count,
				'children_count' => $application->children_count,
				'all_children_live_constantly' => $application->all_children_live_constantly,
				'plays_music' => $application->plays_music,
				'musical_instruments' => $application->musical_instruments,
				'has_pets' => $application->has_pets,
				'pets_description' => $application->pets_description,
				'remarks' => $application->remarks,
			],

			'applicants' => $people->map(fn (Applicant $a) => $this->applicant($a))->all(),

			'children' => $application->children
				->map(fn ($child) => ['birth_year' => $child->birth_year])
				->values()
				->all(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function applicant(Applicant $a): array
	{
		// Field labels/structure mirror the SPA detail panels (ApplicantPanel,
		// EmployerPanel, HousingPanel) so the PDF reads identically to the app:
		// some fields are composed (nationality, landlord) just like there.
		return [
			'is_main' => $a->role === 'main_applicant',
			'salutation' => $a->salutation?->label(),
			'name' => $this->name($a),
			'relationship_to_main' => $a->relationship_to_main?->label(),
			'address' => $this->address($a),
			'birth_date' => $this->date($a->birth_date),
			'marital_status' => $a->marital_status?->label(),
			'nationality' => $this->nationality($a),
			'mobile_phone' => $this->phone($a->mobile_phone),
			'landline_phone' => $this->phone($a->landline_phone),
			'email' => $a->email,
			'occupation' => $a->occupation,
			'employment_status' => $a->employment_status?->label(),
			'debt_enforcement_last_2y' => $a->debt_enforcement_last_2y,

			'employer' => $a->employer ? [
				'name' => $a->employer->name,
				'workload_percent' => $a->employer->workload_percent !== null
					? $a->employer->workload_percent.'%'
					: null,
				'annual_income_bracket' => $a->employer->annual_income_bracket_slug?->label(),
			] : null,

			'current_housing' => $a->currentHousing ? [
				'tenant_role' => $a->currentHousing->tenant_role?->label(),
				'terminated_by_landlord' => $a->currentHousing->terminated_by_landlord,
				'termination_reason' => $a->currentHousing->termination_reason,
				'landlord' => $this->landlord($a->currentHousing),
				'rent_duration' => $a->currentHousing->rent_duration_slug?->label(),
				'previous_landlord' => $a->currentHousing->previous_landlord,
			] : null,
		];
	}

	/** First + last name (salutation is its own "Anrede" row, as in the panel). */
	private function name(Applicant $a): ?string
	{
		return trim(implode(' ', array_filter([$a->first_name, $a->last_name]))) ?: null;
	}

	/**
	 * Nationality plus origin/permit on one line, mirroring ApplicantPanel:
	 * "Schweiz · Heimatort Chur GR" or "Italien · Ausweis C".
	 */
	private function nationality(Applicant $a): ?string
	{
		if (! $a->nationality) {
			return null;
		}

		$parts = [$a->nationality->label()];

		if ($a->nationality->value === 'CH' && $a->place_of_origin) {
			$parts[] = 'Heimatort '.$a->place_of_origin;
		} elseif ($a->residence_permit) {
			$parts[] = 'Ausweis '.$a->residence_permit->label();
		}

		return implode(' · ', $parts);
	}

	/** Landlord name · contact · phone on one line, mirroring HousingPanel. */
	private function landlord(CurrentHousing $ch): ?string
	{
		return implode(' · ', array_filter([
			$ch->landlord_name,
			$ch->landlord_contact_person,
			$this->phone($ch->landlord_phone),
		])) ?: null;
	}

	private function address(Applicant $a): ?string
	{
		if ($a->same_address_as_main) {
			return 'Wie Hauptmieter';
		}

		$street = trim(implode(' ', array_filter([$a->street, $a->street_number])));
		$city = trim(implode(' ', array_filter([$a->postal_code, $a->city])));

		return implode(', ', array_filter([$street, $city])) ?: null;
	}

	private function date(?CarbonInterface $date): ?string
	{
		return $date?->format('d.m.Y');
	}

	/** Swiss amount like the SPA's fmtMoney: "1'800.00" (no currency prefix). */
	private function money(string|float|null $amount): ?string
	{
		if ($amount === null || $amount === '') {
			return null;
		}

		return number_format((float) $amount, 2, '.', "'");
	}

	/**
	 * Swiss phone grouping, mirroring the SPA's fmtPhone: "+41 79 409 49 27".
	 * Non-E.164 / foreign numbers are returned as stored, never mangled.
	 */
	private function phone(?string $value): ?string
	{
		if (! $value) {
			return null;
		}

		$compact = preg_replace('/\s+/', '', $value);

		if (preg_match('/^\+41(\d{9})$/', $compact, $m)) {
			$d = $m[1];

			return '+41 '.substr($d, 0, 2).' '.substr($d, 2, 3).' '.substr($d, 5, 2).' '.substr($d, 7, 2);
		}

		return $value;
	}

	/**
	 * Map a list of preference slugs to their enum labels, ordered by the enum's
	 * own sortOrder() so districts/floors/rooms read consistently regardless of
	 * pivot insertion order. Unknown slugs are dropped.
	 *
	 * @param  class-string	 $enum
	 * @param  array<int, string>  $slugs
	 * @return array<int, string>
	 */
	private function labels(string $enum, array $slugs): array
	{
		return collect($slugs)
			->map(fn (string $slug) => $enum::tryFrom($slug))
			->filter()
			->sortBy(fn ($case) => $case->sortOrder())
			->map(fn ($case) => $case->label())
			->values()
			->all();
	}
}
