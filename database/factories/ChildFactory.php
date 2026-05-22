<?php

namespace Database\Factories;

use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Child>
 */
class ChildFactory extends Factory
{
	protected $model = Child::class;

	public function definition(): array
	{
		return [
			'position' => 1,
			'birth_year' => fake()->numberBetween(2010, (int) date('Y') - 1),
		];
	}
}
