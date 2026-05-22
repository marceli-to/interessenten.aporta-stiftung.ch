<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\StatusEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatusEvent>
 */
class StatusEventFactory extends Factory
{
	protected $model = StatusEvent::class;

	public function definition(): array
	{
		return [
			'actor_user_id' => null,
			'from_status' => null,
			'to_status' => Status::Opened->value,
			'occurred_at' => now(),
			'reason' => null,
		];
	}
}
