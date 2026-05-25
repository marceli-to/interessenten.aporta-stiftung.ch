<?php

namespace App\Actions\Application;

use App\Enums\Status;
use App\Models\Application;
use App\Models\StatusEvent;
use Carbon\CarbonInterface;

class RecordStatusEventAction
{
	/**
	 * Record a status transition. If $from is not provided, it defaults to the
	 * application's current status (back-office flow). Pass $from = null explicitly
	 * for the initial event recorded at intake (from null → Opened).
	 */
	public function execute(
		Application $application,
		Status $to,
		?Status $from = null,
		?int $actorUserId = null,
		?string $reason = null,
		?CarbonInterface $occurredAt = null,
		bool $isInitial = false,
	): StatusEvent {
		if (! $isInitial && $from === null) {
			$from = $application->status;
		}

		if ($application->status !== $to) {
			$application->status = $to;
			$application->save();
		}

		return StatusEvent::create([
			'application_id' => $application->id,
			'actor_user_id' => $actorUserId,
			'from_status' => $from?->value,
			'to_status' => $to->value,
			'occurred_at' => $occurredAt ?? now(),
			'reason' => $reason,
		]);
	}
}
