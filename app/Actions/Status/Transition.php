<?php

namespace App\Actions\Status;

use App\Enums\Status;
use App\Models\Application;

/**
 * Drive a status transition from the back office: flip the status and write the
 * audit StatusEvent (via Status\Record), then stamp the transition date for the
 * target state when the client supplied one.
 *
 * This is the dashboard counterpart to the generic Application\Update — status
 * changes go through here so every transition leaves an audit trail attributed
 * to the acting user.
 */
class Transition
{
	public function __construct(
		private Record $record = new Record(),
	) {}

	/**
	 * @param  array<string, mixed>  $transitionDate  ['extended_at' => …] or ['archived_at' => …],
	 *                                                 empty to leave the column untouched.
	 */
	public function execute(
		Application $application,
		Status $to,
		?int $actorUserId = null,
		?string $reason = null,
		array $transitionDate = [],
	): Application {
		$this->record->execute(
			application: $application,
			to: $to,
			actorUserId: $actorUserId,
			reason: $reason,
		);

		if ($transitionDate !== []) {
			$application->fill($transitionDate)->save();
		}

		return $application;
	}
}
