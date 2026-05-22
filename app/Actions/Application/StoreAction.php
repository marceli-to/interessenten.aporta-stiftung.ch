<?php

namespace App\Actions\Application;

use App\Enums\Status;
use App\Jobs\NotifyNewApplication;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Child;
use App\Models\CurrentHousing;
use App\Models\Employer;
use App\Models\StatusEvent;
use App\Support\ReferenceNumberSequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class StoreAction
{
	public function execute(array $data, ?ReferenceNumberSequence $sequence = null): Application
	{
		$sequence ??= app(ReferenceNumberSequence::class);

		$existing = Application::where('submission_id', $data['submission_id'])->first();
		if ($existing) {
			return $existing;
		}

		try {
			$application = DB::transaction(function () use ($data, $sequence) {
				return $this->createAggregate($data, $sequence);
			});
		} catch (QueryException $e) {
			// Race: another worker inserted the same submission_id between our SELECT and INSERT.
			// Re-fetch and return the winner.
			$existing = Application::where('submission_id', $data['submission_id'])->first();
			if ($existing) {
				return $existing;
			}
			throw $e;
		}

		NotifyNewApplication::dispatch($application->id);

		return $application;
	}

	private function createAggregate(array $data, ReferenceNumberSequence $sequence): Application
	{
		$housing = $data['housing_wish'];
		$household = $data['household_info'];
		$submittedAt = $data['submitted_meta']['submitted_at'];

		$application = Application::create([
			'reference_number' => $sequence->next(),
			'status' => Status::Opened,
			'flagged' => false,
			'opened_at' => $submittedAt,
			'last_changed_at' => $submittedAt,
			'shares_apartment' => $data['shares_apartment'],
			'submission_id' => $data['submission_id'],
			'submitted_ip' => $data['submitted_meta']['ip'],
			'submitted_user_agent' => $data['submitted_meta']['user_agent'],
			'dsg_consent_accepted_at' => $data['dsg_consent_accepted_at'],

			// Housing wish (inlined)
			'wants_balcony' => $housing['wants_balcony'] ?? null,
			'wants_elevator' => $housing['wants_elevator'] ?? null,
			'max_gross_rent' => $housing['max_gross_rent'],
			'earliest_move_in' => $housing['earliest_move_in'],

			// Household info (inlined)
			'total_persons' => $household['total_persons'],
			'adults_count' => $household['adults_count'],
			'children_count' => $household['children_count'],
			'all_children_live_constantly' => $household['all_children_live_constantly'] ?? null,
			'plays_music' => $household['plays_music'],
			'musical_instruments' => $household['musical_instruments'] ?? null,
			'has_pets' => $household['has_pets'],
			'pets_description' => $household['pets_description'] ?? null,
			'remarks' => $household['remarks'] ?? null,
		]);

		$this->createApplicant($application, $data['main_applicant'], role: 'main_applicant', position: 1);

		if (! empty($data['co_applicant'])) {
			$this->createApplicant($application, $data['co_applicant'], role: 'co_applicant', position: 2);
		}

		foreach (array_unique($housing['districts']) as $slug) {
			DB::table('application_districts')->insert([
				'application_id' => $application->id,
				'district_slug' => $slug,
			]);
		}
		foreach (array_unique($housing['floors']) as $slug) {
			DB::table('application_floors')->insert([
				'application_id' => $application->id,
				'floor_slug' => $slug,
			]);
		}
		foreach (array_unique($housing['rooms']) as $slug) {
			DB::table('application_rooms')->insert([
				'application_id' => $application->id,
				'room_slug' => $slug,
			]);
		}

		foreach ($data['children'] ?? [] as $child) {
			Child::create([
				'application_id' => $application->id,
				'position' => $child['position'],
				'birth_year' => $child['birth_year'],
			]);
		}

		StatusEvent::create([
			'application_id' => $application->id,
			'actor_user_id' => null,
			'from_status' => null,
			'to_status' => Status::Opened->value,
			'occurred_at' => $submittedAt,
			'reason' => null,
		]);

		return $application;
	}

	private function createApplicant(Application $application, array $data, string $role, int $position): Applicant
	{
		$applicant = Applicant::create([
			'application_id' => $application->id,
			'role' => $role,
			'position' => $position,
			'salutation' => $data['salutation'],
			'first_name' => $data['first_name'],
			'last_name' => $data['last_name'],
			'street' => $data['street'] ?? null,
			'street_number' => $data['street_number'] ?? null,
			'postal_code' => $data['postal_code'] ?? null,
			'city' => $data['city'] ?? null,
			'same_address_as_main' => $data['same_address_as_main'] ?? null,
			'birth_date' => $data['birth_date'],
			'marital_status' => $data['marital_status'],
			'nationality' => $data['nationality'],
			'place_of_origin' => $data['place_of_origin'] ?? null,
			'residence_permit' => $data['residence_permit'] ?? null,
			'swiss_residence_since' => $data['swiss_residence_since'] ?? null,
			'mobile_phone' => $data['mobile_phone'],
			'landline_phone' => $data['landline_phone'] ?? null,
			'email' => $data['email'],
			'occupation' => $data['occupation'],
			'employment_status' => $data['employment_status'],
			'debt_enforcement_last_2y' => $data['debt_enforcement_last_2y'],
			'relationship_to_main' => $data['relationship_to_main'] ?? null,
		]);

		if (! empty($data['employer'])) {
			Employer::create([
				'applicant_id' => $applicant->id,
				'name' => $data['employer']['name'],
				'workload_percent' => $data['employer']['workload_percent'],
				'annual_income_bracket_slug' => $data['employer']['annual_income_bracket'],
			]);
		}

		$ch = $data['current_housing'];
		CurrentHousing::create([
			'applicant_id' => $applicant->id,
			'tenant_role' => $ch['tenant_role'],
			'terminated_by_landlord' => $ch['terminated_by_landlord'],
			'termination_reason' => $ch['termination_reason'] ?? null,
			'landlord_name' => $ch['landlord_name'],
			'landlord_contact_person' => $ch['landlord_contact_person'] ?? null,
			'landlord_phone' => $ch['landlord_phone'] ?? null,
			'rent_duration_slug' => $ch['rent_duration'],
			'previous_landlord' => $ch['previous_landlord'] ?? null,
		]);

		return $applicant;
	}
}
