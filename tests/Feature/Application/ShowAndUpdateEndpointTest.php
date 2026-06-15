<?php

use App\Jobs\NotifyNewApplication;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
	$this->rawKey = 'test-intake-key-'.bin2hex(random_bytes(8));
	config()->set('aporta.intake_api_key_hash', hash('sha256', $this->rawKey));
	Carbon::setTestNow('2026-05-22T10:30:00Z');
	Bus::fake([NotifyNewApplication::class]);

	$this->postJson('/api/v1/applications', laafifFixture(), [
		'Authorization' => 'Bearer '.$this->rawKey,
	])->assertStatus(201);

	$this->application = Application::first();
	$this->user = User::factory()->create();
});

afterEach(function () {
	Carbon::setTestNow();
});

it('returns the full detail payload with raw values', function () {
	$response = $this->actingAs($this->user)
		->getJson("/api/dashboard/applications/{$this->application->id}");

	$response->assertOk()
		// Raw enum slugs (not labels) so the SPA can bind them into form controls.
		->assertJsonPath('data.main_applicant.last_name', 'Laâfif')
		->assertJsonPath('data.main_applicant.current_housing.rent_duration', 'less_than_1_year')
		->assertJsonPath('data.main_applicant.employer.annual_income_bracket', '30k_40k')
		->assertJsonPath('data.housing_wish.districts', ['kreis_4', 'kreis_5'])
		->assertJsonPath('data.housing_wish.rooms', ['rooms_2_0'])
		->assertJsonPath('data.co_applicant', null)
		->assertJsonPath('data.status.value', 'opened');
});

it('requires authentication', function () {
	$this->getJson("/api/dashboard/applications/{$this->application->id}")
		->assertUnauthorized();
});

it('still resolves the detail of a soft-deleted application', function () {
	$this->application->delete();

	$this->actingAs($this->user)
		->getJson("/api/dashboard/applications/{$this->application->id}")
		->assertOk()
		->assertJsonPath('data.main_applicant.last_name', 'Laâfif');
});

it('updates only the housing_wish section and leaves the rest untouched', function () {
	$originalOccupation = $this->application->mainApplicant->occupation;

	$response = $this->actingAs($this->user)
		->putJson("/api/dashboard/applications/{$this->application->id}", [
			'housing_wish' => [
				'earliest_move_in' => '2026-10-01',
				'max_gross_rent' => '1800.00',
				'wants_balcony' => false,
				'wants_elevator' => true,
				'districts' => ['kreis_6'],
				'floors' => ['eg_hochparterre', 'obergeschoss'],
			],
		]);

	$response->assertOk()
		->assertJsonPath('data.housing_wish.max_gross_rent', '1800.00')
		->assertJsonPath('data.housing_wish.districts', ['kreis_6'])
		// Rooms is derived from persons (1 → [2]); editing the housing wish leaves it.
		->assertJsonPath('data.housing_wish.rooms', ['rooms_2_0'])
		->assertJsonPath('data.housing_wish.wants_elevator', true);

	// Pivots replaced, not appended.
	expect(DB::table('application_districts')->where('application_id', $this->application->id)->count())->toBe(1);

	// Main applicant section was not in the payload → untouched.
	expect($this->application->mainApplicant->fresh()->occupation)->toBe($originalOccupation);
});

it('recomputes the derived room range when the household size changes', function () {
	// Seeded applicant is a 1-person household → rooms [2].
	expect(DB::table('application_rooms')->where('application_id', $this->application->id)->pluck('room_slug')->all())
		->toBe(['rooms_2_0']);

	// Grow to a 3-person household → eligible rooms become persons ± 1 = 2, 3, 4.
	$this->actingAs($this->user)
		->putJson("/api/dashboard/applications/{$this->application->id}", [
			'household_info' => [
				'adults_count' => 2,
				'children_count' => 1,
				'total_persons' => 3,
				'plays_music' => false,
				'has_pets' => false,
			],
			'children' => [['position' => 1, 'birth_year' => 2020]],
		])
		->assertOk()
		->assertJsonPath('data.housing_wish.rooms', ['rooms_2_0', 'rooms_3_0', 'rooms_4_0']);
});

it('replaces the whole main applicant on a round-trip and preserves the employer', function () {
	$detail = $this->actingAs($this->user)
		->getJson("/api/dashboard/applications/{$this->application->id}")
		->json('data.main_applicant');

	$detail['occupation'] = 'Lokführer';

	$response = $this->actingAs($this->user)
		->putJson("/api/dashboard/applications/{$this->application->id}", [
			'main_applicant' => $detail,
		]);

	$response->assertOk()
		->assertJsonPath('data.main_applicant.occupation', 'Lokführer')
		->assertJsonPath('data.main_applicant.employer.workload_percent', 80);

	expect(Employer::count())->toBe(1);
	expect(Applicant::where('application_id', $this->application->id)->count())->toBe(1);
});

it('changes the status and records an audit event', function () {
	expect($this->application->status->value)->toBe('opened');

	$response = $this->actingAs($this->user)
		->putJson("/api/dashboard/applications/{$this->application->id}/status", [
			'status' => 'archived',
		]);

	$response->assertOk()
		->assertJsonPath('data.status.value', 'archived')
		// Verlauf trail, newest first: the change we just made, then the
		// actor-less intake event ("Über Webformular").
		->assertJsonCount(2, 'data.status_events')
		->assertJsonPath('data.status_events.0.status.value', 'archived')
		->assertJsonPath('data.status_events.0.status.label', 'Archiviert')
		->assertJsonPath('data.status_events.0.actor', $this->user->full_name)
		->assertJsonPath('data.status_events.1.status.value', 'opened')
		->assertJsonPath('data.status_events.1.actor', null);

	expect($this->application->fresh()->status->value)->toBe('archived');

	$this->assertDatabaseHas('status_events', [
		'application_id' => $this->application->id,
		'from_status' => 'opened',
		'to_status' => 'archived',
		'actor_user_id' => $this->user->id,
	]);
});

it('does not record an audit event when the status is unchanged', function () {
	expect($this->application->status->value)->toBe('opened');

	$response = $this->actingAs($this->user)
		->putJson("/api/dashboard/applications/{$this->application->id}/status", [
			'status' => 'opened',
		]);

	// Only the intake event survives — re-saving the same status is a no-op
	// for the audit trail, not an "opened → opened" row.
	$response->assertOk()
		->assertJsonCount(1, 'data.status_events')
		->assertJsonPath('data.status_events.0.status.value', 'opened');

	$this->assertDatabaseMissing('status_events', [
		'application_id' => $this->application->id,
		'from_status' => 'opened',
		'to_status' => 'opened',
	]);
});

it('stamps the transition date for the target state', function () {
	$response = $this->actingAs($this->user)
		->putJson("/api/dashboard/applications/{$this->application->id}/status", [
			'status' => 'extended',
			'extended_at' => '2026-05-20',
		]);

	$response->assertOk()->assertJsonPath('data.status.value', 'extended');

	expect($this->application->fresh()->extended_at->toDateString())->toBe('2026-05-20');
});

it('rejects an unknown status with 422', function () {
	$this->actingAs($this->user)
		->putJson("/api/dashboard/applications/{$this->application->id}/status", [
			'status' => 'bogus',
		])
		->assertStatus(422)
		->assertJsonValidationErrors('status');
});

it('rejects a half-filled section with 422', function () {
	$detail = $this->actingAs($this->user)
		->getJson("/api/dashboard/applications/{$this->application->id}")
		->json('data.main_applicant');

	unset($detail['email']);

	$this->actingAs($this->user)
		->putJson("/api/dashboard/applications/{$this->application->id}", [
			'main_applicant' => $detail,
		])
		->assertStatus(422)
		->assertJsonValidationErrors('main_applicant.email');
});
