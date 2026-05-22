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
		$main = $this->application->mainApplicant;

		return new Content(
			htmlString: view()->exists('mail.new_application_stored')
				? (string) view('mail.new_application_stored', [
					'application' => $this->application,
					'mainApplicant' => $main,
					'backofficeUrl' => url('/dashboard/applications/'.$this->application->id),
				])
				: $this->fallbackBody($main),
		);
	}

	private function fallbackBody($main): string
	{
		$name = $main ? trim($main->first_name.' '.$main->last_name) : '–';
		$address = $main ? trim(($main->street ?? '').' '.($main->street_number ?? '').', '.($main->postal_code ?? '').' '.($main->city ?? '')) : '–';
		$ref = $this->application->reference_number;
		$url = url('/dashboard/applications/'.$this->application->id);

		return <<<HTML
			<p>Eine neue Wohnungsanmeldung Nr. <strong>{$ref}</strong> ist eingegangen.</p>
			<p><strong>{$name}</strong><br>{$address}</p>
			<p><a href="{$url}">Im Backoffice öffnen</a></p>
		HTML;
	}
}
