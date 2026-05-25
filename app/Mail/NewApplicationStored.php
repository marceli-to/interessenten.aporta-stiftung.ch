<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewApplicationStored extends Mailable
{
	use Queueable, SerializesModels;

	public function __construct(public Application $application)
	{
	}

	public function envelope(): Envelope
	{
		return new Envelope(
			subject: "Neue Wohnungsanmeldung Nr. {$this->application->reference_number}",
		);
	}

	public function content(): Content
	{
		return new Content(
			markdown: 'mail.new_application_stored',
			with: [
				'application' => $this->application,
				'mainApplicant' => $this->application->mainApplicant,
				'backofficeUrl' => url('/dashboard/applications/'.$this->application->id),
			],
		);
	}
}
