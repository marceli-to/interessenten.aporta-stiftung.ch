<?php

namespace Database\Factories;

use App\Models\HouseholdInfo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HouseholdInfo>
 */
class HouseholdInfoFactory extends Factory
{
	protected $model = HouseholdInfo::class;

	public function definition(): array
	{
		return [
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
}
