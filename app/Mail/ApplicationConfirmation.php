<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationConfirmation extends Mailable
{
	use Queueable, SerializesModels;

	public $theme = 'aporta';

	public function __construct(public Application $application)
	{
	}

	public function envelope(): Envelope
	{
		return new Envelope(
			subject: 'Vielen Dank für Ihre Anfrage',
			replyTo: [new Address('wohnung@aporta-stiftung.ch')],
		);
	}

	public function content(): Content
	{
		return new Content(
			markdown: 'mail.application_confirmation',
		);
	}
}
