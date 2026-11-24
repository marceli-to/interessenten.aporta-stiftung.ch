<?php

namespace App\Actions\Application;

use App\Actions\Status\Record;
use App\Enums\Status;
use App\Jobs\SendRenewalConfirmation;
use App\Models\Application;
use Illuminate\Support\Facades\DB;

class Renew
{
	private Update $update;

	public function __construct(
		private Record $record = new Record(),
		?Update $update = null,
	) {
		$this->update = $update ?? app(Update::class);
	}

	/**
	 * Renew an application on behalf of the applicant (renewal link email):
	 * optionally apply their updated data, then move it to Extended with a new
	 * extended_at — which starts the next validity cycle, clears "link sent"
	 * and invalidates all renewal links of the previous cycle.
	 *
	 * The main applicant's email is not editable here: it is the address the
	 * renewal link goes to, so a change has to go through the office.
	 *
	 * Always writes a StatusEvent (even Extended → Extended) without an actor,
	 * so the Verlauf shows the renewal and its reason.
	 */
	public function execute(Application $application, ?array $data = null): Application
	{
		DB::transaction(function () use ($application, $data) {
			if ($data !== null) {
				$application->loadMissing('mainApplicant');
				$data['main_applicant']['email'] = $application->mainApplicant?->email;

				$this->update->execute($application, $data);
				$application->refresh();
			}

			$this->record->execute(
				application: $application,
				to: Status::Extended,
				reason: $data === null
					? 'Vom Interessenten per E-Mail bestätigt'
					: 'Vom Interessenten aktualisiert',
				recordUnchanged: true,
			);

			$application->extended_at = now();
			$application->save();
		});

		SendRenewalConfirmation::dispatch($application->id);

		return $application;
	}
}
