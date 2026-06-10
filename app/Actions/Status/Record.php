<?php

namespace App\Actions\Status;

use App\Enums\Status;
use App\Models\Application;
use App\Models\StatusEvent;
use Carbon\CarbonInterface;

class Record
{
	/**
	 * Record a status transition. If $from is not provided, it defaults to the
	 * application's current status (back-office flow). Pass $from = null explicitly
	 * for the initial event recorded at intake (from null → Opened).
	 *
	 * A no-op save (status unchanged) records nothing and returns null: the audit
	 * log is a trail of real transitions, not of every "Speichern" click. The
	 * initial intake event is exempt — it is the genesis entry (null → Opened).
	 */
	public function execute(
		Application $application,
		Status $to,
		?Status $from = null,
		?int $actorUserId = null,
		?string $reason = null,
		?CarbonInterface $occurredAt = null,
		bool $isInitial = false,
	): ?StatusEvent {
		if (! $isInitial && $from === null) {
			$from = $application->status;
		}

		if (! $isInitial && $from === $to) {
			return null;
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
