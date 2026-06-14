<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Application;
use App\Support\ReferenceNumberSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
	protected $model = Application::class;

	public function definition(): array
	{
		$openedAt = fake()->dateTimeBetween('-3 months', 'now');

		return [
			'reference_number' => fn () => app(ReferenceNumberSequence::class)->next(),
			'status' => Status::Opened,
			'opened_at' => $openedAt,
			'extended_at' => null,
			'archived_at' => null,
			'last_changed_at' => $openedAt,
			'shares_apartment' => false,
			'submitted_ip' => fake()->ipv4(),
			'submitted_user_agent' => fake()->userAgent(),
			'submission_id' => fake()->uuid(),

			// Housing wish
			'wants_balcony' => true,
			'wants_elevator' => false,
			'max_gross_rent' => fake()->randomElement(['1600.00', '1800.00', '2200.00']),
			'earliest_move_in' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
			'property_group' => null,
			'property_class' => null,

			// Household info
			'total_persons' => 1,
			'adults_count' => 1,
			'children_count' => 0,
			'all_children_live_constantly' => null,
			'plays_music' => false,
			'musical_instruments' => null,
			'has_pets' => false,
			'pets_description' => null,
			'remarks' => null,
		];
	}

	public function withFullAggregate(): self
	{
		return $this->has(ApplicantFactory::new()->mainApplicant(), 'applicants');
	}
}
