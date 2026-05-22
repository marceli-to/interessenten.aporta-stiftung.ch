<?php

namespace App\Jobs;

use App\Mail\NewApplicationStored;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyNewApplication implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public function __construct(public int $applicationId)
	{
	}

	public function handle(): void
	{
		$recipient = config('aporta.new_application_notify_email');
		if (! $recipient) {
			Log::warning('aporta.new_application_notify_email is not configured; skipping new-application notification.');

			return;
		}

		$application = Application::with('mainApplicant')->find($this->applicationId);
		if (! $application) {
			Log::warning("NotifyNewApplication: application {$this->applicationId} not found.");

			return;
		}

		Mail::to($recipient)->send(new NewApplicationStored($application));
	}
}
