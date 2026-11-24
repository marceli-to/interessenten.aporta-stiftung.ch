<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RenewalConfirmation extends Mailable
{
	use Queueable, SerializesModels;

	public $theme = 'aporta';

	public function __construct(public Application $application)
	{
	}

	public function envelope(): Envelope
	{
		return new Envelope(
			subject: 'Ihre Wohnungsbewerbung wurde verlängert',
			replyTo: [new Address('wohnung@aporta-stiftung.ch')],
		);
	}

	public function content(): Content
	{
		return new Content(
			markdown: 'mail.renewal_confirmation',
			with: [
				'validUntil' => $this->application->validUntil()->format('d.m.Y'),
			],
		);
	}
}
