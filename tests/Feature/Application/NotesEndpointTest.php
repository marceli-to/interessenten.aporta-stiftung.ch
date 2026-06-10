<?php

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
	$this->rawKey = 'test-intake-key-'.bin2hex(random_bytes(8));
	config()->set('aporta.intake_api_key_hash', hash('sha256', $this->rawKey));
	Carbon::setTestNow('2026-05-22T10:30:00Z');

	$this->postJson('/api/v1/applications', laafifFixture(), [
		'Authorization' => 'Bearer '.$this->rawKey,
	])->assertStatus(201);

	$this->application = Application::first();
	$this->user = User::factory()->create(['firstname' => 'Anna', 'name' => 'Serio']);
});

afterEach(function () {
	Carbon::setTestNow();
});

it('adds a note and returns just the created note', function () {
	$response = $this->actingAs($this->user)
		->postJson("/api/dashboard/applications/{$this->application->id}/notes", [
			'body' => "Erstkontakt telefonisch.\nUnterlagen vollständig.",
		]);

	$response->assertCreated()
		->assertJsonPath('data.body', "Erstkontakt telefonisch.\nUnterlagen vollständig.")
		->assertJsonPath('data.author', 'Anna Serio')
		->assertJsonPath('data.created_at', '2026-05-22T10:30:00+00:00');

	$this->assertDatabaseHas('notes', [
		'application_id' => $this->application->id,
		'user_id' => $this->user->id,
		'important' => false,
	]);
});

it('orders notes newest first', function () {
	Carbon::setTestNow('2026-01-15T09:00:00Z');
	$this->application->notes()->create(['body' => 'Older', 'user_id' => $this->user->id]);
	Carbon::setTestNow('2026-04-05T09:00:00Z');
	$this->application->notes()->create(['body' => 'Newer', 'user_id' => $this->user->id]);

	$this->actingAs($this->user)
		->getJson("/api/dashboard/applications/{$this->application->id}")
		->assertJsonPath('data.notes.0.body', 'Newer')
		->assertJsonPath('data.notes.1.body', 'Older');
});

it('rejects an empty note body with 422', function () {
	$this->actingAs($this->user)
		->postJson("/api/dashboard/applications/{$this->application->id}/notes", ['body' => '  '])
		->assertStatus(422)
		->assertJsonValidationErrors('body');
});

it('updates a note body', function () {
	$note = $this->application->notes()->create(['body' => 'Draft', 'user_id' => $this->user->id]);

	$this->actingAs($this->user)
		->putJson("/api/dashboard/applications/{$this->application->id}/notes/{$note->id}", ['body' => 'Final'])
		->assertOk()
		->assertJsonPath('data.id', $note->id)
		->assertJsonPath('data.body', 'Final');

	expect($note->fresh()->body)->toBe('Final');
});

it('deletes a note', function () {
	$note = $this->application->notes()->create(['body' => 'Remove me', 'user_id' => $this->user->id]);

	$this->actingAs($this->user)
		->deleteJson("/api/dashboard/applications/{$this->application->id}/notes/{$note->id}")
		->assertNoContent();

	$this->assertDatabaseMissing('notes', ['id' => $note->id]);
});

it('will not touch a note belonging to another application', function () {
	$other = Application::factory()->create();
	$note = $other->notes()->create(['body' => 'Foreign', 'user_id' => $this->user->id]);

	$this->actingAs($this->user)
		->putJson("/api/dashboard/applications/{$this->application->id}/notes/{$note->id}", ['body' => 'Hijacked'])
		->assertNotFound();

	expect($note->fresh()->body)->toBe('Foreign');
});

it('requires authentication', function () {
	$this->postJson("/api/dashboard/applications/{$this->application->id}/notes", ['body' => 'x'])
		->assertUnauthorized();
});
