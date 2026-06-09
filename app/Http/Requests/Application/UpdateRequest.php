<?php

namespace App\Http\Requests\Application;

use App\Http\Requests\Application\Concerns\ApplicationPayloadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Partial, per-section update from the dashboard detail view. Every top-level
 * section is `sometimes` (via sectionRequired: false in the shared rules), so a
 * panel may PUT just its own slice — e.g. { "housing_wish": { … } } — and the
 * other sections are left untouched. Fields inside a present section are still
 * required (required_with), so a half-filled section is rejected.
 */
class UpdateRequest extends FormRequest
{
	use ApplicationPayloadRules;

	public function authorize(): bool
	{
		// Route is behind the `auth` middleware; field-level authorization
		// matches the rest of the dashboard (no per-role policy yet).
		return true;
	}

	protected function prepareForValidation(): void
	{
		$this->replace($this->normalizeApplicantPhones($this->all()));
	}

	public function rules(): array
	{
		return array_merge(
			[
				'shares_apartment' => ['sometimes', 'boolean'],
				'flagged' => ['sometimes', 'boolean'],
			],
			$this->housingWishRules(sectionRequired: false),
			$this->householdInfoRules(sectionRequired: false),
			$this->childrenRules(sectionRequired: false),
			$this->applicantRules('main_applicant', isMain: true, sectionRequired: false),
			$this->applicantRules('co_applicant', isMain: false, sectionRequired: false),
		);
	}

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator) {
			$data = $validator->getData();

			// Only enforce the household arithmetic when that section is being
			// saved — other panels don't carry these fields.
			if (! array_key_exists('household_info', $data)) {
				return;
			}

			$household = is_array($data['household_info'] ?? null) ? $data['household_info'] : [];
			$totalPersons = $household['total_persons'] ?? null;
			$adults = $household['adults_count'] ?? null;
			$childrenCount = $household['children_count'] ?? null;

			if (is_int($totalPersons) && is_int($adults) && is_int($childrenCount)) {
				if ($totalPersons !== $adults + $childrenCount) {
					$validator->errors()->add(
						'household_info.total_persons',
						'total_persons muss gleich adults_count + children_count sein.'
					);
				}
			}

			if (is_int($childrenCount) && array_key_exists('children', $data) && is_array($data['children'])) {
				if (count($data['children']) !== $childrenCount) {
					$validator->errors()->add(
						'children',
						"children muss genau {$childrenCount} Einträge enthalten."
					);
				}
			}
		});
	}
}
