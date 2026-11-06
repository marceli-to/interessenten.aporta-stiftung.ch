<?php

use App\Actions\Application\Store;
use App\Enums\Status;
use App\Jobs\SendRenewalLink;
use App\Mail\ApplicationErased;
use App\Mail\RenewalConfirmation;
use App\Mail\RenewalLink;
use App\Models\Application;
use App\Models\StatusEvent;
use App\Models\User;
use App\Support\RenewalToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
	Carbon::setTestNow('2026-10-06T08:00:00Z');

	$this->rawKey = 'test-intake-key-'.bin2hex(random_bytes(8));
	config()->set('aporta.intake_api_key_hash', hash('sha256', $this->rawKey));
});

afterEach(function () {
	Carbon::setTestNow();
});

/**
 * A full application (main applicant, housing, employer…) via the intake
 * action, opened at the given date.
 */
function renewableApplication(Carbon $openedAt, array $attributes = []): Application
{
	Mail::fake();
	$application = (new Store())->execute(
		array_merge(laafifFixture(), ['submission_id' => (string) Str::ulid()])
	);

	$application->forceFill(array_merge(['opened_at' => $openedAt], $attributes))->save();

	return $application->fresh();
}

/* ------------------------------------------------------------ renewal link */

it('sends the renewal link from the dashboard and records when', function () {
	Queue::fake([SendRenewalLink::class]);
	$application = renewableApplication(now()->subMonths(7));

	$this->actingAs(User::factory()->create())
		->postJson("/api/dashboard/applications/{$application->id}/renewal-link")
		->assertOk()
		->assertJsonPath('data.is_renewable', true)
		->assertJsonPath('data.renewal_link_sent_at', now()->toIso8601String());

	Queue::assertPushed(SendRenewalLink::class, fn ($job) => $job->applicationId === $application->id);
	expect($application->fresh()->renewal_link_sent_at)->not->toBeNull();
});

it('can send the link again', function () {
	Queue::fake([SendRenewalLink::class]);
	$application = renewableApplication(now()->subMonths(2));
	$user = User::factory()->create();

	$this->actingAs($user)->postJson("/api/dashboard/applications/{$application->id}/renewal-link")->assertOk();
	$this->actingAs($user)->postJson("/api/dashboard/applications/{$application->id}/renewal-link")->assertOk();

	Queue::assertPushed(SendRenewalLink::class, 2);
});

it('sends the link for archived applications too', function () {
	Queue::fake([SendRenewalLink::class]);
	$application = renewableApplication(now()->subYear(), ['status' => Status::Archived, 'archived_at' => now()->subMonth()]);

	$this->actingAs(User::factory()->create())
		->postJson("/api/dashboard/applications/{$application->id}/renewal-link")
		->assertOk();

	Queue::assertPushed(SendRenewalLink::class, 1);
});

it('refuses the link for KNIF or email-less applications', function () {
	Queue::fake([SendRenewalLink::class]);
	$user = User::factory()->create();
	$knif = renewableApplication(now()->subMonths(6), ['status' => Status::Knif]);
	$noEmail = renewableApplication(now()->subMonths(6));
	$noEmail->mainApplicant->update(['email' => '']);

	foreach ([$knif, $noEmail] as $application) {
		$this->actingAs($user)
			->postJson("/api/dashboard/applications/{$application->id}/renewal-link")
			->assertStatus(422)
			->assertJsonValidationErrors('renewal');
		expect($application->fresh()->renewal_link_sent_at)->toBeNull();
	}

	Queue::assertNothingPushed();
});

it('requires a dashboard login to send the link', function () {
	$application = renewableApplication(now()->subMonths(6));

	$this->postJson("/api/dashboard/applications/{$application->id}/renewal-link")->assertStatus(401);
});

it('clears "link sent" when the application is renewed', function () {
	$application = renewableApplication(now()->subMonths(6), ['renewal_link_sent_at' => now()]);

	$application->update(['extended_at' => now()]);

	expect($application->fresh()->renewal_link_sent_at)->toBeNull();
});

it('does not send anything automatically', function () {
	expect(collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
		->filter(fn ($event) => str_contains($event->command ?? '', 'renewal')))->toBeEmpty();
});

it('mails the renewal link with working links to the main applicant', function () {
	$application = renewableApplication(now()->subMonths(6));
	Mail::fake();

	(new SendRenewalLink($application->id))->handle();

	Mail::assertSent(RenewalLink::class, function (RenewalLink $mail) use ($application) {
		$html = $mail->render();
		$token = RenewalToken::for($application);

		return $mail->hasTo('redalaafif@gmail.com')
			&& str_contains($html, route('renewal.confirm', $token))
			&& str_contains($html, route('renewal.delete', $token))
			&& str_contains($html, '?t='.$token);
	});
});

/* ------------------------------------------------------------------- tokens */

it('rejects tampered tokens and tokens of a previous cycle', function () {
	$application = renewableApplication(now()->subMonths(5));
	$token = RenewalToken::for($application);

	expect(RenewalToken::resolve($token)?->id)->toBe($application->id);
	expect(RenewalToken::resolve($token.'0'))->toBeNull();
	expect(RenewalToken::resolve(str_replace('.'.$application->id.'.', '.', '.'.$token)))->toBeNull();

	$application->update(['extended_at' => now()]);

	expect(RenewalToken::resolve($token))->toBeNull();
});

/* -------------------------------------------------------------- landing page */

it('does not change anything when a link is only opened', function () {
	$application = renewableApplication(now()->subMonths(6));
	$token = RenewalToken::for($application);

	$this->get(route('renewal.confirm', $token))->assertOk()->assertSee('Angaben bestätigen');
	$this->get(route('renewal.delete', $token))->assertOk()->assertSee('endgültig löschen');

	$application->refresh();
	expect($application->status)->toBe(Status::Opened);
	expect($application->extended_at)->toBeNull();
});

it('extends the application when the applicant confirms', function () {
	$application = renewableApplication(now()->subMonths(6));
	$token = RenewalToken::for($application);
	Mail::fake();

	$this->post(route('renewal.confirm.store', $token))
		->assertRedirect(route('renewal.done'));

	$application->refresh();
	expect($application->status)->toBe(Status::Extended);
	expect($application->extended_at->toDateString())->toBe(now()->toDateString());
	expect(StatusEvent::where('application_id', $application->id)->latest('id')->first()->reason)
		->toBe('Vom Interessenten per E-Mail bestätigt');
	Mail::assertSent(RenewalConfirmation::class);

	// The link is used up.
	$this->post(route('renewal.confirm.store', $token))->assertSee('Link nicht mehr gültig');
});

it('records a renewal of an already extended application', function () {
	$application = renewableApplication(now()->subYear(), [
		'status' => Status::Extended,
		'extended_at' => now()->subMonths(6),
	]);

	$this->post(route('renewal.confirm.store', RenewalToken::for($application)));

	expect(StatusEvent::where('application_id', $application->id)->where('to_status', 'extended')->count())->toBe(1);
	expect($application->fresh()->extended_at->toDateString())->toBe(now()->toDateString());
});

it('permanently deletes the application and its activity log on request', function () {
	$application = renewableApplication(now()->subMonths(6));
	$applicantId = $application->mainApplicant->id;
	Mail::fake();

	$this->post(route('renewal.delete.store', RenewalToken::for($application)))
		->assertRedirect(route('renewal.done'));

	expect(Application::withTrashed()->find($application->id))->toBeNull();
	expect(Activity::where('subject_type', (new Application())->getMorphClass())->where('subject_id', $application->id)->exists())->toBeFalse();
	expect(Activity::where('subject_type', (new App\Models\Applicant())->getMorphClass())->where('subject_id', $applicantId)->exists())->toBeFalse();
	Mail::assertQueued(ApplicationErased::class, fn ($mail) => $mail->hasTo('redalaafif@gmail.com'));
});

it('reactivates an archived application when the applicant confirms', function () {
	$application = renewableApplication(now()->subYear(), ['status' => Status::Archived, 'archived_at' => now()->subMonth()]);
	Mail::fake();

	$this->post(route('renewal.confirm.store', RenewalToken::for($application)))
		->assertRedirect(route('renewal.done'));

	$application->refresh();
	expect($application->status)->toBe(Status::Extended);
	expect($application->extended_at->toDateString())->toBe(now()->toDateString());
	expect(StatusEvent::where('application_id', $application->id)->latest('id')->first()->from_status)
		->toBe(Status::Archived);
});

it('shows the invalid page for KNIF and deleted applications', function () {
	$knif = renewableApplication(now()->subMonths(6));
	$knifToken = RenewalToken::for($knif);
	$knif->update(['status' => Status::Knif]);

	$this->get(route('renewal.confirm', $knifToken))->assertSee('Link nicht mehr gültig');
	$this->post(route('renewal.delete.store', $knifToken))->assertSee('Link nicht mehr gültig');
	expect(Application::find($knif->id))->not->toBeNull();

	$trashed = renewableApplication(now()->subMonths(6));
	$trashedToken = RenewalToken::for($trashed);
	$trashed->delete();

	$this->get(route('renewal.confirm', $trashedToken))->assertSee('Link nicht mehr gültig');
});

/* ---------------------------------------------------------------------- API */

it('requires the intake key for the renewal api', function () {
	$token = RenewalToken::for(renewableApplication(now()->subMonths(6)));

	$this->getJson("/api/v1/renewals/{$token}")->assertStatus(401);
});

it('returns the application in the intake payload shape', function () {
	$application = renewableApplication(now()->subMonths(6));
	$token = RenewalToken::for($application);

	$response = $this->getJson("/api/v1/renewals/{$token}", ['Authorization' => 'Bearer '.$this->rawKey])
		->assertOk()
		->assertJsonPath('meta.reference_number', $application->reference_number)
		->assertJsonPath('data.main_applicant.email', 'redalaafif@gmail.com')
		->assertJsonPath('data.main_applicant.employer.annual_income_bracket', '30k_40k')
		->assertJsonPath('data.housing_wish.districts', ['kreis_4', 'kreis_5']);

	expect($response->json('data'))->not->toHaveKeys(['notes', 'status_events', 'id']);
	expect($response->json('data.housing_wish'))->not->toHaveKey('rooms');
});

it('updates and extends the application, keeping the main email', function () {
	$application = renewableApplication(now()->subMonths(6));
	$token = RenewalToken::for($application);
	Mail::fake();

	$payload = (new App\Actions\Application\RenewalPayload())->execute($application);
	$payload['main_applicant']['city'] = 'Winterthur';
	$payload['main_applicant']['email'] = 'someone-else@example.com';
	$payload['housing_wish']['max_gross_rent'] = '1900.00';
	$payload['housing_wish']['earliest_move_in'] = now()->addMonth()->toDateString();

	$this->putJson("/api/v1/renewals/{$token}", $payload, ['Authorization' => 'Bearer '.$this->rawKey])
		->assertOk()
		->assertJsonPath('data.valid_until', now()->addMonths(6)->toDateString());

	$application->refresh();
	expect($application->status)->toBe(Status::Extended);
	expect($application->max_gross_rent)->toBe('1900.00');
	expect($application->mainApplicant->city)->toBe('Winterthur');
	expect($application->mainApplicant->email)->toBe('redalaafif@gmail.com');
	expect(StatusEvent::where('application_id', $application->id)->latest('id')->first()->reason)
		->toBe('Vom Interessenten aktualisiert');

	// The token is used up.
	$this->getJson("/api/v1/renewals/{$token}", ['Authorization' => 'Bearer '.$this->rawKey])->assertStatus(410);
});

it('rejects invalid updates without extending', function () {
	$application = renewableApplication(now()->subMonths(6));
	$token = RenewalToken::for($application);

	$payload = (new App\Actions\Application\RenewalPayload())->execute($application);
	unset($payload['main_applicant']['last_name']);

	$this->putJson("/api/v1/renewals/{$token}", $payload, ['Authorization' => 'Bearer '.$this->rawKey])
		->assertStatus(422);

	expect($application->fresh()->status)->toBe(Status::Opened);
});
