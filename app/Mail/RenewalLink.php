<?php

namespace App\Mail;

use App\Models\Application;
use App\Support\RenewalToken;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RenewalLink extends Mailable
{
	use Queueable, SerializesModels;

	public $theme = 'aporta';

	public function __construct(public Application $application)
	{
	}

	public function envelope(): Envelope
	{
		return new Envelope(
			subject: 'Verlängerung Ihrer Wohnungsbewerbung',
			replyTo: [new Address('wohnung@aporta-stiftung.ch')],
		);
	}

	public function content(): Content
	{
		$token = RenewalToken::for($this->application);

		return new Content(
			markdown: 'mail.renewal_link',
			with: [
				'name' => trim($this->application->mainApplicant?->first_name.' '.$this->application->mainApplicant?->last_name),
				'confirmUrl' => route('renewal.confirm', $token),
				'updateUrl' => config('aporta.renewal.form_url').'?t='.urlencode($token),
				'deleteUrl' => route('renewal.delete', $token),
			],
		);
	}
}
