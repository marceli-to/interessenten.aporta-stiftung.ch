<?php

use App\Jobs\NotifyNewApplication;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

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

it('exposes deleted_at on the detail payload so the SPA can show the restore panel', function () {
	$this->actingAs($this->user)
		->getJson("/api/dashboard/applications/{$this->application->id}")
		->assertOk()
		->assertJsonPath('data.deleted_at', null);

	$this->application->delete();

	$this->actingAs($this->user)
		->getJson("/api/dashboard/applications/{$this->application->id}")
		->assertOk()
		->assertJsonPath('data.deleted_at', '2026-05-22T10:30:00+00:00');
});

it('restores a soft-deleted application and returns the live detail payload', function () {
	$this->application->delete();
	expect(Application::find($this->application->id))->toBeNull();

	$this->actingAs($this->user)
		->postJson("/api/dashboard/applications/{$this->application->id}/restore")
		->assertOk()
		->assertJsonPath('data.id', $this->application->id)
		->assertJsonPath('data.deleted_at', null);

	expect(Application::find($this->application->id))->not->toBeNull();
	expect($this->application->fresh()->deleted_at)->toBeNull();
});

it('is a no-op on an application that is not trashed', function () {
	$this->actingAs($this->user)
		->postJson("/api/dashboard/applications/{$this->application->id}/restore")
		->assertOk()
		->assertJsonPath('data.deleted_at', null);

	expect($this->application->fresh()->deleted_at)->toBeNull();
});

it('requires authentication', function () {
	$this->application->delete();

	$this->postJson("/api/dashboard/applications/{$this->application->id}/restore")
		->assertUnauthorized();
});
