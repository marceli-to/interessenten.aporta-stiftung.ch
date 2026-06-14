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
	$this->user = User::factory()->create();
});

afterEach(function () {
	Carbon::setTestNow();
});

/**
 * Submit N applications through the intake API and return their fresh models,
 * newest first (matching the default list order).
 */
function submitApplications(int $count): \Illuminate\Support\Collection
{
	for ($i = 0; $i < $count; $i++) {
		$payload = laafifFixture();
		// The fixture carries a fixed submission_id; intake is idempotent on it,
		// so vary it (valid ULID) to create distinct applications.
		$payload['submission_id'] = (string) \Illuminate\Support\Str::ulid();

		test()->postJson('/api/v1/applications', $payload, [
			'Authorization' => 'Bearer '.test()->rawKey,
		])->assertStatus(201);
	}

	return Application::orderByDesc('id')->get();
}

it('soft-deletes an explicit set of ids and returns the count', function () {
	$apps = submitApplications(3);
	$victims = $apps->take(2)->pluck('id')->all();

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-delete', ['ids' => $victims])
		->assertOk()
		->assertJsonPath('deleted', 2);

	expect(Application::whereIn('id', $victims)->count())->toBe(0);
	expect(Application::withTrashed()->whereIn('id', $victims)->whereNotNull('deleted_at')->count())->toBe(2);
	// The third survives.
	expect(Application::count())->toBe(1);
});

it('soft-deletes everything matching a filter (all-matching mode)', function () {
	$apps = submitApplications(3);

	// All three share the same status from the fixture; filter by it and delete.
	$status = $apps->first()->status->value;

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-delete', ['status' => $status])
		->assertOk()
		->assertJsonPath('deleted', 3);

	expect(Application::count())->toBe(0);
});

it('honours exclusions in all-matching mode', function () {
	$apps = submitApplications(3);
	$status = $apps->first()->status->value;
	$keep = $apps->first()->id;

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-delete', [
			'status' => $status,
			'exclude' => [$keep],
		])
		->assertOk()
		->assertJsonPath('deleted', 2);

	expect(Application::count())->toBe(1);
	expect(Application::find($keep))->not->toBeNull();
});

it('rejects a bulk delete with neither ids nor a filter', function () {
	submitApplications(2);

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-delete', [])
		->assertStatus(422)
		->assertJsonValidationErrors('ids');

	// Nothing deleted.
	expect(Application::count())->toBe(2);
});

it('skips already-trashed rows in the count', function () {
	$apps = submitApplications(2);
	$apps->first()->delete(); // pre-trash one

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-delete', [
			'ids' => $apps->pluck('id')->all(),
		])
		->assertOk()
		// Only the one still-live row counts as deleted by this call.
		->assertJsonPath('deleted', 1);

	expect(Application::count())->toBe(0);
});

it('requires authentication', function () {
	$apps = submitApplications(1);

	$this->postJson('/api/dashboard/applications/bulk-delete', [
		'ids' => $apps->pluck('id')->all(),
	])->assertUnauthorized();

	expect(Application::count())->toBe(1);
});
