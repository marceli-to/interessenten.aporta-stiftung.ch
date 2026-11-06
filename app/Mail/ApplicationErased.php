<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent after the applicant deleted their application. Carries only the
 * reference number — the application no longer exists.
 */
class ApplicationErased extends Mailable
{
	use Queueable, SerializesModels;

	public $theme = 'aporta';

	public function __construct(public int $referenceNumber)
	{
	}

	public function envelope(): Envelope
	{
		return new Envelope(
			subject: 'Ihre Wohnungsbewerbung wurde gelöscht',
			replyTo: [new Address('wohnung@aporta-stiftung.ch')],
		);
	}

	public function content(): Content
	{
		return new Content(
			markdown: 'mail.application_erased',
		);
	}
}
