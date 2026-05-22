<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Enums\Nationality;
use App\Enums\Salutation;
use App\Models\Applicant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Applicant>
 */
class ApplicantFactory extends Factory
{
	protected $model = Applicant::class;

	public function definition(): array
	{
		return [
			'role' => 'main_applicant',
			'position' => 0,
			'salutation' => Salutation::Herr,
			'first_name' => fake()->firstName(),
			'last_name' => fake()->lastName(),
			'street' => fake()->streetName(),
			'street_number' => (string) fake()->numberBetween(1, 200),
			'postal_code' => (string) fake()->numberBetween(8000, 8099),
			'city' => 'Zürich',
			'same_address_as_main' => null,
			'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
			'marital_status' => MaritalStatus::Single,
			'nationality' => Nationality::CH,
			'place_of_origin' => 'Luzern',
			'residence_permit' => null,
			'swiss_residence_since' => null,
			'mobile_phone' => '+41763694020',
			'landline_phone' => null,
			'email' => fake()->unique()->safeEmail(),
			'occupation' => fake()->jobTitle(),
			'employment_status' => EmploymentStatus::Employed,
			'debt_enforcement_last_2y' => false,
			'relationship_to_main' => null,
		];
	}

	public function mainApplicant(): self
	{
		return $this->state([
			'role' => 'main_applicant',
			'position' => 0,
		]);
	}

	public function coApplicant(int $position = 1): self
	{
		return $this->state([
			'role' => 'co_applicant',
			'position' => $position,
			'relationship_to_main' => \App\Enums\RelationshipToMain::LifePartner,
			'same_address_as_main' => true,
			'street' => null,
			'street_number' => null,
			'postal_code' => null,
			'city' => null,
		]);
	}
}
