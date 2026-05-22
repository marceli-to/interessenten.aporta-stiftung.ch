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
			'flagged' => false,
			'opened_at' => $openedAt,
			'extended_at' => null,
			'archived_at' => null,
			'last_changed_at' => $openedAt,
			'owner_user_id' => null,
			'shares_apartment' => false,
			'submitted_ip' => fake()->ipv4(),
			'submitted_user_agent' => fake()->userAgent(),
			'submission_id' => fake()->uuid(),
		];
	}

	public function withFullAggregate(): self
	{
		return $this
			->has(ApplicantFactory::new()->mainApplicant(), 'applicants')
			->has(HousingWishFactory::new(), 'housingWish')
			->has(HouseholdInfoFactory::new(), 'householdInfo');
	}
}
