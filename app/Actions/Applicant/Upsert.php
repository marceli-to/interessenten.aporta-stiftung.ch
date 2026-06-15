<?php

namespace App\Actions\Applicant;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\CurrentHousing;
use App\Models\Employer;

class Upsert
{
	public function execute(Application $application, array $data, string $role, int $position): Applicant
	{
		$applicant = Applicant::updateOrCreate(
			['application_id' => $application->id, 'role' => $role],
			[
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
			]
		);

		Employer::where('applicant_id', $applicant->id)->delete();
		if (! empty($data['employer'])) {
			Employer::create([
				'applicant_id' => $applicant->id,
				'name' => $data['employer']['name'],
				'workload_percent' => $data['employer']['workload_percent'],
				'annual_income_bracket_slug' => $data['employer']['annual_income_bracket'],
			]);
		}

		$ch = $data['current_housing'];
		CurrentHousing::updateOrCreate(
			['applicant_id' => $applicant->id],
			[
				'tenant_role' => $ch['tenant_role'],
				'terminated_by_landlord' => $ch['terminated_by_landlord'],
				'termination_reason' => $ch['termination_reason'] ?? null,
				'landlord_name' => $ch['landlord_name'],
				'landlord_contact_person' => $ch['landlord_contact_person'] ?? null,
				'landlord_phone' => $ch['landlord_phone'] ?? null,
			]
		);

		return $applicant;
	}
}
