<?php

namespace App\Http\Controllers;

use App\Actions\Application\Erase;
use App\Actions\Application\Renew;
use App\Mail\ApplicationErased;
use App\Support\RenewalToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Landing pages behind the links in the renewal link email.
 *
 * Opening a link never changes anything: mail scanners (Safe Links & co.)
 * fetch every URL in a message. The GET pages only show what will happen;
 * the action runs on the POST from the button.
 */
class RenewalController extends Controller
{
	public function confirm(string $token): View
	{
		return $this->page('confirm', $token);
	}

	public function storeConfirm(string $token, Renew $renew): RedirectResponse|View
	{
		$application = RenewalToken::resolve($token);
		if (! $application) {
			return $this->invalid();
		}

		$renew->execute($application);

		return redirect()->route('renewal.done')->with('renewal', [
			'state' => 'confirmed',
			'valid_until' => $application->validUntil()->format('d.m.Y'),
		]);
	}

	public function delete(string $token): View
	{
		return $this->page('delete', $token);
	}

	public function storeDelete(string $token, Erase $erase): RedirectResponse|View
	{
		$application = RenewalToken::resolve($token);
		if (! $application) {
			return $this->invalid();
		}

		$referenceNumber = $application->reference_number;
		$email = $application->mainApplicant?->email;

		$erase->execute($application);

		Log::info('Application erased by applicant', ['reference_number' => $referenceNumber]);

		if ($email) {
			Mail::to($email)->queue(new ApplicationErased($referenceNumber));
		}

		return redirect()->route('renewal.done')->with('renewal', ['state' => 'deleted']);
	}

	/**
	 * Result page after the POST (post/redirect/get). The token is no longer
	 * valid at this point, so the result travels in the session flash.
	 */
	public function done(): View|RedirectResponse
	{
		$result = session('renewal');

		if (! is_array($result)) {
			return $this->invalid();
		}

		return view('pages.renewal', $result);
	}

	private function page(string $state, string $token): View
	{
		$application = RenewalToken::resolve($token);
		if (! $application) {
			return $this->invalid();
		}

		return view('pages.renewal', [
			'state' => $state,
			'token' => $token,
			'referenceNumber' => $application->reference_number,
			'validUntil' => $application->validUntil()->format('d.m.Y'),
			'updateUrl' => config('aporta.renewal.form_url').'?t='.urlencode($token),
		]);
	}

	private function invalid(): View
	{
		return view('pages.renewal', ['state' => 'invalid']);
	}
}
