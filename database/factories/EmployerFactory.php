<?php

namespace Database\Factories;

use App\Enums\IncomeBracket;
use App\Models\Employer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employer>
 */
class EmployerFactory extends Factory
{
	protected $model = Employer::class;

	public function definition(): array
	{
		return [
			'name' => fake()->company(),
			'workload_percent' => fake()->numberBetween(20, 100),
			'annual_income_bracket_slug' => IncomeBracket::From60kTo70k->value,
		];
	}
}
