<?php

namespace App\Console\Commands;

use App\Mail\NewApplicationStored;
use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestApplicationMail extends Command
{
	protected $signature = 'mail:test-application
		{to? : Recipient email (defaults to MAIL_TO or mail.from.address)}
		{--id= : Specific application id to render; otherwise the latest is used}';

	protected $description = 'Send the NewApplicationStored email for quick visual testing';

	public function handle(): int
	{
		$query = Application::with('mainApplicant');
		$application = $this->option('id')
			? $query->find($this->option('id'))
			: $query->latest('id')->first();

		if (! $application) {
			$this->error('No application found. Pass --id=… or seed one first.');
			return self::FAILURE;
		}

		$recipient = $this->argument('to')
			?? env('MAIL_TO')
			?? config('mail.from.address');

		if (! $recipient) {
			$this->error('No recipient — pass an address, set MAIL_TO, or configure mail.from.address.');
			return self::FAILURE;
		}

		Mail::to($recipient)->send(new NewApplicationStored($application));

		$this->info("Sent application #{$application->id} to {$recipient}.");
		return self::SUCCESS;
	}
}
