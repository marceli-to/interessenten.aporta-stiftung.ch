<?php

namespace App\Http\Requests\Application;

use App\Enums\District;
use App\Enums\EmploymentStatus;
use App\Enums\Floor;
use App\Enums\IncomeBracket;
use App\Enums\MaritalStatus;
use App\Enums\Nationality;
use App\Enums\RelationshipToMain;
use App\Enums\RentDuration;
use App\Enums\ResidencePermit;
use App\Enums\Room;
use App\Enums\Salutation;
use App\Enums\TenantRole;
use App\Support\PhoneNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	protected function prepareForValidation(): void
	{
		$normalizer = app(PhoneNormalizer::class);

		$payload = $this->all();

		$normalizeApplicant = function (array $applicant) use ($normalizer): array {
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
			$payload['main_applicant'] = $normalizeApplicant($payload['main_applicant']);
		}
		if (isset($payload['co_applicant']) && is_array($payload['co_applicant'])) {
			$payload['co_applicant'] = $normalizeApplicant($payload['co_applicant']);
		}

		$this->replace($payload);
	}

	public function rules(): array
	{
		$nowPlus5 = now()->addMinutes(5)->toIso8601String();

		return array_merge(
			[
				'submission_id' => ['required', 'string', 'between:16,48', $this->submissionIdRule()],
				'submitted_meta' => ['required', 'array'],
				'submitted_meta.ip' => ['required', 'ip'],
				'submitted_meta.user_agent' => ['required', 'string', 'max:512'],
				'submitted_meta.submitted_at' => ['required', 'date', 'before_or_equal:'.$nowPlus5],
				'dsg_consent_accepted_at' => ['required', 'date'],

				'shares_apartment' => ['required', 'boolean'],

				'housing_wish' => ['required', 'array'],
				'housing_wish.earliest_move_in' => ['required', 'date', 'after_or_equal:today'],
				'housing_wish.max_gross_rent' => ['required', 'numeric', 'min:1200', 'max:20000'],
				'housing_wish.wants_balcony' => ['nullable', 'boolean'],
				'housing_wish.wants_elevator' => ['nullable', 'boolean'],
				'housing_wish.districts' => ['required', 'array', 'min:1'],
				'housing_wish.districts.*' => ['string', Rule::enum(District::class)],
				'housing_wish.floors' => ['required', 'array', 'min:1'],
				'housing_wish.floors.*' => ['string', Rule::enum(Floor::class)],
				'housing_wish.rooms' => ['required', 'array', 'min:1'],
				'housing_wish.rooms.*' => ['string', Rule::enum(Room::class)],

				'household_info' => ['required', 'array'],
				'household_info.total_persons' => ['required', 'integer', 'min:1'],
				'household_info.adults_count' => ['required', 'integer', 'min:1'],
				'household_info.children_count' => ['required', 'integer', 'min:0'],
				'household_info.all_children_live_constantly' => ['nullable', 'boolean'],
				'household_info.plays_music' => ['required', 'boolean'],
				'household_info.musical_instruments' => ['nullable', 'string', 'max:200', 'required_if:household_info.plays_music,true'],
				'household_info.has_pets' => ['required', 'boolean'],
				'household_info.pets_description' => ['nullable', 'string', 'max:200', 'required_if:household_info.has_pets,true'],
				'household_info.remarks' => ['nullable', 'string', 'max:5000'],

				'children' => ['present', 'array'],
				'children.*.position' => ['required', 'integer', 'min:1'],
				'children.*.birth_year' => ['required', 'integer', 'min:1900', 'max:'.((int) date('Y'))],

				'co_applicant' => ['nullable', 'array'],
			],
			$this->applicantRules('main_applicant', isMain: true),
			$this->applicantRules('co_applicant', isMain: false),
		);
	}

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator) {
			$data = $validator->getData();

			$totalPersons = $data['household_info']['total_persons'] ?? null;
			$adults = $data['household_info']['adults_count'] ?? null;
			$childrenCount = $data['household_info']['children_count'] ?? null;
			$children = $data['children'] ?? [];

			if (is_int($totalPersons) && is_int($adults) && is_int($childrenCount)) {
				if ($totalPersons !== $adults + $childrenCount) {
					$validator->errors()->add(
						'household_info.total_persons',
						'total_persons muss gleich adults_count + children_count sein.'
					);
				}
			}

			if (is_int($childrenCount) && is_array($children) && count($children) !== $childrenCount) {
				$validator->errors()->add(
					'children',
					"children muss genau {$childrenCount} Einträge enthalten."
				);
			}
		});
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	private function applicantRules(string $key, bool $isMain): array
	{
		$prefix = $key;
		$required = $isMain ? 'required' : 'required_with:'.$key;

		$rules = [
			$prefix => [$isMain ? 'required' : 'nullable', 'array'],

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
			"$prefix.current_housing.rent_duration" => [$required, 'string', Rule::enum(RentDuration::class)],
			"$prefix.current_housing.previous_landlord" => ['nullable', 'string', 'max:200', "required_if:$prefix.current_housing.rent_duration,less_than_1_year"],
		];

		if ($isMain) {
			$rules["$prefix.street"] = ['required', 'string', 'max:200'];
			$rules["$prefix.street_number"] = ['required', 'string', 'max:20'];
			$rules["$prefix.postal_code"] = ['required', 'string', 'max:10'];
			$rules["$prefix.city"] = ['required', 'string', 'max:100'];
		} else {
			$rules["$prefix.relationship_to_main"] = [$required, 'string', Rule::enum(RelationshipToMain::class)];
			$rules["$prefix.same_address_as_main"] = [$required, 'boolean'];
			$rules["$prefix.street"] = ['nullable', 'string', 'max:200', "required_if:$prefix.same_address_as_main,false"];
			$rules["$prefix.street_number"] = ['nullable', 'string', 'max:20', "required_if:$prefix.same_address_as_main,false"];
			$rules["$prefix.postal_code"] = ['nullable', 'string', 'max:10', "required_if:$prefix.same_address_as_main,false"];
			$rules["$prefix.city"] = ['nullable', 'string', 'max:100', "required_if:$prefix.same_address_as_main,false"];
		}

		return $rules;
	}

	private function submissionIdRule(): Closure
	{
		return function (string $attribute, mixed $value, Closure $fail) {
			if (! is_string($value)) {
				$fail('submission_id muss eine Zeichenkette sein.');

				return;
			}
			$isUlid = (bool) preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $value);
			$isUuid = (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
			if (! $isUlid && ! $isUuid) {
				$fail('submission_id muss eine ULID oder UUID v4 sein.');
			}
		};
	}

	/**
	 * Only enforce when the applicant block is actually present in the payload
	 * (co_applicant may legitimately be null) AND nationality is non-Swiss.
	 *
	 * @return array<int, string>
	 */
	private function nonSwissRequiredRule(string $prefix): array
	{
		$applicantPresent = $this->input($prefix) !== null;
		$nationality = $this->input("$prefix.nationality");

		if ($applicantPresent && is_string($nationality) && $nationality !== 'CH') {
			return ['required'];
		}

		return [];
	}

	private function phoneRule(): Closure
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
