<?php

namespace App\Jobs;

use App\Mail\ApplicationConfirmation;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendApplicationConfirmation implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public function __construct(public int $applicationId)
	{
	}

	public function handle(): void
	{
		$application = Application::with('mainApplicant')->find($this->applicationId);
		if (! $application) {
			Log::warning("SendApplicationConfirmation: application {$this->applicationId} not found.");

			return;
		}

		$recipient = $application->mainApplicant?->email;
		if (! $recipient) {
			Log::warning("SendApplicationConfirmation: application {$this->applicationId} has no main-applicant email; skipping confirmation.");

			return;
		}

		Mail::to($recipient)->send(new ApplicationConfirmation($application));
	}
}
