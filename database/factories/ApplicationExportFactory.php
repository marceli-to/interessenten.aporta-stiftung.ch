<?php

namespace Database\Factories;

use App\Enums\ExportStatus;
use App\Models\ApplicationExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationExport>
 */
class ApplicationExportFactory extends Factory
{
	protected $model = ApplicationExport::class;

	public function definition(): array
	{
		return [
			'user_id' => User::factory(),
			'status' => ExportStatus::Pending,
			'disk' => null,
			'path' => null,
			'application_count' => null,
			'failure_reason' => null,
			'expires_at' => null,
		];
	}

	public function ready(): static
	{
		return $this->state(fn () => [
			'status' => ExportStatus::Ready,
			'disk' => 'local',
			'path' => 'exports/'.$this->faker->uuid().'.pdf',
			'application_count' => $this->faker->numberBetween(1, 50),
			'expires_at' => now()->addDay(),
		]);
	}

	public function failed(): static
	{
		return $this->state(fn () => [
			'status' => ExportStatus::Failed,
			'failure_reason' => 'Rendering failed',
		]);
	}

	public function expired(): static
	{
		return $this->ready()->state(fn () => [
			'expires_at' => now()->subHour(),
		]);
	}
}
