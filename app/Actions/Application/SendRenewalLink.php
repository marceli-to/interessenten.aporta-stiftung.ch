<?php

namespace App\Actions\Application;

use App\Jobs\SendRenewalLink as SendRenewalLinkJob;
use App\Models\Application;
use Illuminate\Validation\ValidationException;

class SendRenewalLink
{
	/**
	 * Email the main applicant the renewal link (confirm, update or delete).
	 *
	 * Kundenvorgabe: nothing goes out automatically. The office sends the link
	 * after the applicant got in touch by email or phone, so only people who are
	 * actively looking stay in the pool. Can be sent again (e.g. mail lost); every
	 * send carries the same token until the application is renewed.
	 *
	 * @throws ValidationException when the application can no longer be renewed
	 *                             or the main applicant has no email address
	 */
	public function execute(Application $application): Application
	{
		$application->loadMissing('mainApplicant');

		if (! $application->isRenewable()) {
			throw ValidationException::withMessages([
				'renewal' => 'Für diese Bewerbung kann kein Verlängerungslink gesendet werden (KNIF oder gelöscht).',
			]);
		}

		if (blank($application->mainApplicant?->email)) {
			throw ValidationException::withMessages([
				'renewal' => 'Die Hauptperson hat keine E-Mail-Adresse.',
			]);
		}

		$application->renewal_link_sent_at = now();
		$application->save();

		SendRenewalLinkJob::dispatch($application->id);

		return $application;
	}
}
