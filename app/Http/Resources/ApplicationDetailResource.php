<?php

namespace App\Http\Resources;

use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full application payload for the detail view. Unlike ApplicationResource (the
 * flat, label-only list shape), this returns RAW values — enum slugs and
 * Y-m-d dates — so the SPA can bind them straight into edit-mode form controls.
 * Display labels are resolved client-side from the /lookups option lists.
 *
 * The section keys mirror Application\Update exactly (housing_wish,
 * household_info, main_applicant, co_applicant, children) so a panel can PUT
 * back the same shape it received.
 */
class ApplicationDetailResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'reference_number' => $this->reference_number,
			'status' => [
				'value' => $this->status->value,
				'label' => $this->status->label(),
			],
			'shares_apartment' => (bool) $this->shares_apartment,
			'opened_at' => $this->opened_at?->toIso8601String(),
			'extended_at' => $this->extended_at?->toIso8601String(),
			'archived_at' => $this->archived_at?->toIso8601String(),
			// Non-null when the application is soft-deleted: the detail view shows
			// the restore panel instead of the delete panel (the "Gelöscht" list
			// opens trashed rows via the withTrashed show route).
			'deleted_at' => $this->deleted_at?->toIso8601String(),
			'last_changed_at' => $this->last_changed_at?->toIso8601String(),

			'housing_wish' => [
				'wants_elevator' => $this->wants_elevator,
				'max_gross_rent' => $this->max_gross_rent,
				'earliest_move_in' => $this->earliest_move_in?->toDateString(),
				'districts' => $this->district_slugs ?? [],
				'floors' => $this->floor_slugs ?? [],
				'rooms' => $this->room_slugs ?? [],
			],

			'household_info' => [
				'total_persons' => $this->total_persons,
				'adults_count' => $this->adults_count,
				'children_count' => $this->children_count,
				'all_children_live_constantly' => $this->all_children_live_constantly,
				'has_pets' => $this->has_pets,
				'pets_description' => $this->pets_description,
				'remarks' => $this->remarks,
			],

			'main_applicant' => $this->applicant($this->mainApplicant),
			'co_applicant' => $this->applicant($this->coApplicants->first()),

			'children' => $this->children
				->map(fn ($child) => [
					'position' => $child->position,
					'birth_year' => $child->birth_year,
				])
				->values(),

			// Initial list for the Notizen panel, newest first (ordered in
			// Application\Show). The panel owns its list from here on.
			'notes' => NoteResource::collection($this->notes),

			// Status audit trail for the Verlauf panel, newest first.
			'status_events' => StatusEventResource::collection($this->statusEvents),
		];
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function applicant(?Applicant $applicant): ?array
	{
		if (! $applicant) {
			return null;
		}

		return [
			'salutation' => $applicant->salutation?->value,
			'first_name' => $applicant->first_name,
			'last_name' => $applicant->last_name,
			'street' => $applicant->street,
			'street_number' => $applicant->street_number,
			'postal_code' => $applicant->postal_code,
			'city' => $applicant->city,
			'same_address_as_main' => $applicant->same_address_as_main,
			'birth_date' => $applicant->birth_date?->toDateString(),
			'marital_status' => $applicant->marital_status?->value,
			'nationality' => $applicant->nationality?->value,
			'place_of_origin' => $applicant->place_of_origin,
			'residence_permit' => $applicant->residence_permit?->value,
			'swiss_residence_since' => $applicant->swiss_residence_since?->toDateString(),
			'mobile_phone' => $applicant->mobile_phone,
			'landline_phone' => $applicant->landline_phone,
			'email' => $applicant->email,
			'occupation' => $applicant->occupation,
			'employment_status' => $applicant->employment_status?->value,
			'debt_enforcement_last_2y' => $applicant->debt_enforcement_last_2y,
			'relationship_to_main' => $applicant->relationship_to_main?->value,

			'employer' => $applicant->employer ? [
				'name' => $applicant->employer->name,
				'workload_percent' => $applicant->employer->workload_percent,
				'annual_income_bracket' => $applicant->employer->annual_income_bracket_slug?->value,
			] : null,

			'current_housing' => $applicant->currentHousing ? [
				'tenant_role' => $applicant->currentHousing->tenant_role?->value,
				'terminated_by_landlord' => $applicant->currentHousing->terminated_by_landlord,
				'termination_reason' => $applicant->currentHousing->termination_reason,
				'landlord_name' => $applicant->currentHousing->landlord_name,
				'landlord_contact_person' => $applicant->currentHousing->landlord_contact_person,
				'landlord_phone' => $applicant->currentHousing->landlord_phone,
			] : null,
		];
	}
}
