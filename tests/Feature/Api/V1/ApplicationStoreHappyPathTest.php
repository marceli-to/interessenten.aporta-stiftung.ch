<?php

use App\Jobs\NotifyNewApplication;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Child;
use App\Models\CurrentHousing;
use App\Models\Employer;
use App\Models\StatusEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
	$this->rawKey = 'test-intake-key-'.bin2hex(random_bytes(8));
	config()->set('aporta.intake_api_key_hash', hash('sha256', $this->rawKey));
	Carbon::setTestNow('2026-05-22T10:30:00Z');
});

afterEach(function () {
	Carbon::setTestNow();
});

it('stores the Laâfif fixture and persists the full aggregate', function () {
	Bus::fake([NotifyNewApplication::class]);

	$response = $this->postJson('/api/v1/applications', laafifFixture(), [
		'Authorization' => 'Bearer '.$this->rawKey,
	]);

	$response->assertStatus(201)
		->assertJsonStructure(['data' => ['reference_number', 'status', 'opened_at']])
		->assertJsonPath('data.status', 'opened');

	expect(Application::count())->toBe(1);
	expect(Applicant::count())->toBe(1);
	expect(Child::count())->toBe(0);
	expect(Employer::count())->toBe(1);
	expect(CurrentHousing::count())->toBe(1);
	expect(StatusEvent::count())->toBe(1);

	$application = Application::first();
	expect($application->submission_id)->toBe('01HZN5XK9R8E3Q4G7P2W6F0VAB');
	expect($application->opened_at?->toIso8601String())->toBe('2026-05-22T10:22:00+00:00');
	expect($application->status->value)->toBe('opened');
	expect((float) $application->max_gross_rent)->toBe(1600.0);
	expect($application->wants_elevator)->toBeFalse();

	$applicant = Applicant::first();
	expect($applicant->role)->toBe('main_applicant');
	expect($applicant->position)->toBe(1);
	expect($applicant->email)->toBe('redalaafif@gmail.com');
	expect($applicant->mobile_phone)->toBe('+41763694020');
	expect($applicant->nationality->value)->toBe('CH');

	expect(Employer::first()->annual_income_bracket_slug->value)->toBe('30k_40k');

	expect($application->statusEvents()->count())->toBe(1);
	$event = $application->statusEvents()->first();
	expect($event->from_status)->toBeNull();
	expect($event->to_status->value)->toBe('opened');
	expect($event->actor_user_id)->toBeNull();

	expect($application->districts = \DB::table('application_districts')->where('application_id', $application->id)->count())->toBe(2);
	expect(\DB::table('application_floors')->where('application_id', $application->id)->count())->toBe(2);
	expect(\DB::table('application_rooms')->where('application_id', $application->id)->count())->toBe(1);

	Bus::assertDispatchedTimes(NotifyNewApplication::class, 1);
});

it('normalizes phones to E.164', function () {
	$data = laafifFixture();
	$data['main_applicant']['mobile_phone'] = '076 369 40 20';

	$this->postJson('/api/v1/applications', $data, [
		'Authorization' => 'Bearer '.$this->rawKey,
	])->assertStatus(201);

	$applicant = Applicant::first();
	expect($applicant->mobile_phone)->toBe('+41763694020');
});

it('uses opened_at from submitted_meta.submitted_at — not now()', function () {
	Carbon::setTestNow('2026-05-22T13:00:00Z');
	$data = laafifFixture();
	$data['submitted_meta']['submitted_at'] = '2026-05-22T10:00:00Z';

	$this->postJson('/api/v1/applications', $data, [
		'Authorization' => 'Bearer '.$this->rawKey,
	])->assertStatus(201);

	expect(Application::first()->opened_at->toIso8601String())->toBe('2026-05-22T10:00:00+00:00');
});
