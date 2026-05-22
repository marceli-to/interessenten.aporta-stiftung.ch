<?php

namespace Database\Factories;

use App\Models\HousingWish;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HousingWish>
 */
class HousingWishFactory extends Factory
{
	protected $model = HousingWish::class;

	public function definition(): array
	{
		return [
			'wants_balcony' => true,
			'wants_elevator' => false,
			'max_gross_rent' => fake()->randomElement(['1600.00', '1800.00', '2200.00']),
			'earliest_move_in' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
			'property_group' => null,
			'property_class' => null,
		];
	}
}
