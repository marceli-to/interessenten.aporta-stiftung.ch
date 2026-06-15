<?php

namespace App\Http\Requests\Application\Concerns;

use App\Enums\District;
use App\Enums\EmploymentStatus;
use App\Enums\Floor;
use App\Enums\IncomeBracket;
use App\Enums\MaritalStatus;
use App\Enums\Nationality;
use App\Enums\RelationshipToMain;
use App\Enums\ResidencePermit;
use App\Enums\Salutation;
use App\Enums\TenantRole;
use App\Support\PhoneNormalizer;
use Closure;
use Illuminate\Validation\Rule;

trait ApplicationPayloadRules
{
	protected function normalizeApplicantPhones(array $payload): array
	{
		$normalizer = app(PhoneNormalizer::class);

		$normalize = function (array $applicant) use ($normalizer): array {
			foreach (['mobile_phone', 'landline_phone'] as $field) {
				if (isset($applicant[$field]) && is_string($applicant[$field])) {
					$applicant[$field] = $normalizer->normalize($applicant[$field]);
				}
			}
			if (isset($applicant['current_housing']['landlord_phone']) && is_string($applicant['current_housing']['landlord_phone'])) {
				$applicant['current_housing']['landlord_phone'] = $normalizer->normalize($applicant['current_housing']['landlord_phone']);
			}

			return $applicant;
		};

		if (isset($payload['main_applicant']) && is_array($payload['main_applicant'])) {
			$payload['main_applicant'] = $normalize($payload['main_applicant']);
		}
		if (isset($payload['co_applicant']) && is_array($payload['co_applicant'])) {
			$payload['co_applicant'] = $normalize($payload['co_applicant']);
		}

		return $payload;
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	protected function housingWishRules(bool $sectionRequired): array
	{
		$req = $sectionRequired ? 'required' : 'sometimes';

		return [
			'housing_wish' => [$req, 'array'],
			'housing_wish.earliest_move_in' => [$req, 'date'],
			'housing_wish.max_gross_rent' => [$req, 'numeric', 'min:1200', 'max:20000'],
			'housing_wish.wants_elevator' => ['nullable', 'boolean'],
			'housing_wish.districts' => [$req, 'array', 'min:1'],
			'housing_wish.districts.*' => ['string', Rule::enum(District::class)],
			'housing_wish.floors' => [$req, 'array', 'min:1'],
			'housing_wish.floors.*' => ['string', Rule::enum(Floor::class)],
		];
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	protected function householdInfoRules(bool $sectionRequired): array
	{
		$req = $sectionRequired ? 'required' : 'sometimes';

		return [
			'household_info' => [$req, 'array'],
			'household_info.total_persons' => [$req, 'integer', 'min:1'],
			'household_info.adults_count' => [$req, 'integer', 'min:1'],
			'household_info.children_count' => [$req, 'integer', 'min:0'],
			'household_info.all_children_live_constantly' => ['nullable', 'boolean'],
			'household_info.has_pets' => [$req, 'boolean'],
			'household_info.pets_description' => ['nullable', 'string', 'max:200', 'required_if:household_info.has_pets,true'],
			'household_info.remarks' => ['nullable', 'string', 'max:5000'],
		];
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	protected function childrenRules(bool $sectionRequired): array
	{
		return [
			'children' => [$sectionRequired ? 'present' : 'sometimes', 'array'],
			'children.*.position' => ['required', 'integer', 'min:1'],
			'children.*.birth_year' => ['required', 'integer', 'min:1900', 'max:'.((int) date('Y'))],
		];
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	protected function applicantRules(string $key, bool $isMain, bool $sectionRequired): array
	{
		$prefix = $key;

		// Section-presence rule at the top level.
		//   intake main      → required (section must be there)
		//   intake co        → nullable (whole block may be omitted/null)
		//   update main      → sometimes (skip the whole block if absent)
		//   update co        → nullable (null means "remove co-applicant")
		$sectionPresence = match (true) {
			$isMain && $sectionRequired => ['required', 'array'],
			$isMain => ['sometimes', 'array'],
			default => ['nullable', 'array'],
		};

		// Required-marker for fields inside the section.
		//   intake main      → 'required'             (section is mandatory, so every field is)
		//   intake co        → 'required_with:co_applicant' (only if block present)
		//   update any       → 'required_with:'.$prefix      (only if block present)
		$required = ($isMain && $sectionRequired) ? 'required' : 'required_with:'.$prefix;

		$rules = [
			$prefix => $sectionPresence,

			"$prefix.salutation" => [$required, 'string', Rule::enum(Salutation::class)],
			"$prefix.first_name" => [$required, 'string', 'max:100'],
			"$prefix.last_name" => [$required, 'string', 'max:100'],
			"$prefix.birth_date" => [$required, 'date', 'before_or_equal:'.now()->subYears(16)->toDateString()],
			"$prefix.marital_status" => [$required, 'string', Rule::enum(MaritalStatus::class)],
			"$prefix.nationality" => [$required, 'string', Rule::enum(Nationality::class)],
			"$prefix.place_of_origin" => ['nullable', 'string', 'max:100', "required_if:$prefix.nationality,CH"],
			"$prefix.residence_permit" => array_merge(
				$this->nonSwissRequiredRule($prefix),
				['nullable', 'string', Rule::enum(ResidencePermit::class)],
			),
			"$prefix.swiss_residence_since" => array_merge(
				$this->nonSwissRequiredRule($prefix),
				['nullable', 'date'],
			),
			"$prefix.mobile_phone" => [$required, 'string', 'max:30', $this->phoneRule()],
			"$prefix.landline_phone" => ['nullable', 'string', 'max:30', $this->phoneRule()],
			"$prefix.email" => [$required, 'email', 'max:255'],
			"$prefix.occupation" => [$required, 'string', 'max:200'],
			"$prefix.employment_status" => [$required, 'string', Rule::enum(EmploymentStatus::class)],
			"$prefix.debt_enforcement_last_2y" => [$required, 'boolean'],

			"$prefix.employer" => ['nullable', 'array', "required_if:$prefix.employment_status,employed"],
			"$prefix.employer.name" => ['required_with:'.$prefix.'.employer', 'string', 'max:200'],
			"$prefix.employer.workload_percent" => ['required_with:'.$prefix.'.employer', 'integer', 'min:1', 'max:100'],
			"$prefix.employer.annual_income_bracket" => ['required_with:'.$prefix.'.employer', 'string', Rule::enum(IncomeBracket::class)],

			"$prefix.current_housing" => [$required, 'array'],
			"$prefix.current_housing.tenant_role" => [$required, 'string', Rule::enum(TenantRole::class)],
			"$prefix.current_housing.terminated_by_landlord" => [$required, 'boolean'],
			"$prefix.current_housing.termination_reason" => ['nullable', 'string', 'max:1000', "required_if:$prefix.current_housing.terminated_by_landlord,true"],
			"$prefix.current_housing.landlord_name" => [$required, 'string', 'max:200'],
			"$prefix.current_housing.landlord_contact_person" => ['nullable', 'string', 'max:200'],
			"$prefix.current_housing.landlord_phone" => ['nullable', 'string', 'max:30', $this->phoneRule()],
		];

		if ($isMain) {
			$rules["$prefix.street"] = [$required, 'string', 'max:200'];
			$rules["$prefix.street_number"] = ['nullable', 'string', 'max:20'];
			$rules["$prefix.postal_code"] = [$required, 'string', 'max:10'];
			$rules["$prefix.city"] = [$required, 'string', 'max:100'];
		} else {
			$rules["$prefix.relationship_to_main"] = [$required, 'string', Rule::enum(RelationshipToMain::class)];
			$rules["$prefix.same_address_as_main"] = [$required, 'boolean'];
			$rules["$prefix.street"] = ['nullable', 'string', 'max:200', "required_if:$prefix.same_address_as_main,false"];
			$rules["$prefix.street_number"] = ['nullable', 'string', 'max:20'];
			$rules["$prefix.postal_code"] = ['nullable', 'string', 'max:10', "required_if:$prefix.same_address_as_main,false"];
			$rules["$prefix.city"] = ['nullable', 'string', 'max:100', "required_if:$prefix.same_address_as_main,false"];
		}

		return $rules;
	}

	/**
	 * Enforce 'required' on residence_permit / swiss_residence_since only when the
	 * applicant block is present in the payload AND nationality is non-Swiss.
	 *
	 * @return array<int, string>
	 */
	protected function nonSwissRequiredRule(string $prefix): array
	{
		$applicantPresent = $this->input($prefix) !== null;
		$nationality = $this->input("$prefix.nationality");

		if ($applicantPresent && is_string($nationality) && $nationality !== 'CH') {
			return ['required'];
		}

		return [];
	}

	protected function phoneRule(): Closure
	{
		return function (string $attribute, mixed $value, Closure $fail) {
			if ($value === null || $value === '') {
				return;
			}
			if (! app(PhoneNormalizer::class)->isValid((string) $value)) {
				$fail('Das Feld :attribute enthält keine gültige Telefonnummer.');
			}
		};
	}
}
