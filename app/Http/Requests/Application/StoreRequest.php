<?php

namespace App\Http\Requests\Application;

use App\Http\Requests\Application\Concerns\ApplicationPayloadRules;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRequest extends FormRequest
{
	use ApplicationPayloadRules;

	public function authorize(): bool
	{
		return true;
	}

	protected function prepareForValidation(): void
	{
		$this->replace($this->normalizeApplicantPhones($this->all()));
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

				'shares_apartment' => ['required', 'boolean'],
			],
			$this->housingWishRules(sectionRequired: true),
			$this->householdInfoRules(sectionRequired: true),
			$this->childrenRules(sectionRequired: true),
			$this->applicantRules('main_applicant', isMain: true, sectionRequired: true),
			$this->applicantRules('co_applicant', isMain: false, sectionRequired: true),
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
}
