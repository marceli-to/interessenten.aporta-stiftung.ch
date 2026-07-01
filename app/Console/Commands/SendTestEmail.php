<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
	protected $signature = 'app:send-test-email {to? : Override recipient; defaults to APORTA_NEW_APPLICATION_NOTIFY_EMAIL}';

	protected $description = 'Send a raw test email to verify SMTP delivery works on this environment.';

	public function handle(): int
	{
		$recipient = $this->argument('to') ?: config('aporta.new_application_notify_email');

		if (! $recipient) {
			$this->error('No recipient. Set APORTA_NEW_APPLICATION_NOTIFY_EMAIL or pass one as an argument.');

			return self::FAILURE;
		}

		$this->line("Sending test email to {$recipient} via ".config('mail.default').' ...');

		Mail::raw(
			'This is a test email from '.config('app.name').' sent at '.now()->toDateTimeString().'. If you received this, SMTP delivery works.',
			fn ($message) => $message
				->to($recipient)
				->subject('['.config('app.name').'] SMTP test')
		);

		$this->info('Sent without error. Check the inbox at '.$recipient.'.');

		return self::SUCCESS;
	}
}
