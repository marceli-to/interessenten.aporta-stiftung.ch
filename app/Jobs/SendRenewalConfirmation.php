<?php

namespace App\Jobs;

use App\Mail\RenewalConfirmation;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRenewalConfirmation implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public function __construct(public int $applicationId)
	{
	}

	public function handle(): void
	{
		$application = Application::with('mainApplicant')->find($this->applicationId);

		if ($recipient = $application?->mainApplicant?->email) {
			Mail::to($recipient)->send(new RenewalConfirmation($application));
		}
	}
}
