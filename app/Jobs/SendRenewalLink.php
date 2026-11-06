<?php

namespace App\Jobs;

use App\Mail\RenewalLink;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRenewalLink implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public function __construct(public int $applicationId)
	{
	}

	public function handle(): void
	{
		$application = Application::with('mainApplicant')->find($this->applicationId);

		// Renewed, deleted or set to KNIF between dispatch and send: the links in
		// the mail would no longer work.
		if (! $application || ! $application->isRenewable()) {
			return;
		}

		$recipient = $application->mainApplicant?->email;
		if (! $recipient) {
			Log::warning("SendRenewalLink: application {$this->applicationId} has no main-applicant email; skipping.");

			return;
		}

		Mail::to($recipient)->send(new RenewalLink($application));
	}
}
