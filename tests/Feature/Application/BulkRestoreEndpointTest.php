<?php

use App\Jobs\NotifyNewApplication;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

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
 * Submit N applications through the intake API (unique submission_id each) and
 * return them newest first.
 */
function submitTrashableApplications(int $count): \Illuminate\Support\Collection
{
	for ($i = 0; $i < $count; $i++) {
		$payload = laafifFixture();
		$payload['submission_id'] = (string) Str::ulid();

		test()->postJson('/api/v1/applications', $payload, [
			'Authorization' => 'Bearer '.test()->rawKey,
		])->assertStatus(201);
	}

	return Application::orderByDesc('id')->get();
}

it('restores an explicit set of trashed ids and returns the count', function () {
	$apps = submitTrashableApplications(3);
	$apps->each->delete(); // trash all three
	$revive = $apps->take(2)->pluck('id')->all();

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-restore', ['ids' => $revive])
		->assertOk()
		->assertJsonPath('restored', 2);

	expect(Application::whereIn('id', $revive)->count())->toBe(2);
	// The third stays trashed.
	expect(Application::count())->toBe(2);
	expect(Application::onlyTrashed()->count())->toBe(1);
});

it('restores everything matching the trashed view (all-matching mode)', function () {
	$apps = submitTrashableApplications(3);
	$apps->each->delete();

	$this->actingAs($this->user)
		// status=deleted is the trashed-view sentinel → onlyTrashed() server-side.
		->postJson('/api/dashboard/applications/bulk-restore', ['status' => 'deleted'])
		->assertOk()
		->assertJsonPath('restored', 3);

	expect(Application::count())->toBe(3);
	expect(Application::onlyTrashed()->count())->toBe(0);
});

it('honours exclusions in all-matching restore', function () {
	$apps = submitTrashableApplications(3);
	$apps->each->delete();
	$keepTrashed = $apps->first()->id;

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-restore', [
			'status' => 'deleted',
			'exclude' => [$keepTrashed],
		])
		->assertOk()
		->assertJsonPath('restored', 2);

	expect(Application::count())->toBe(2);
	expect(Application::onlyTrashed()->pluck('id')->all())->toBe([$keepTrashed]);
});

it('rejects a bulk restore with neither ids nor a filter', function () {
	$apps = submitTrashableApplications(2);
	$apps->each->delete();

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-restore', [])
		->assertStatus(422)
		->assertJsonValidationErrors('ids');

	expect(Application::onlyTrashed()->count())->toBe(2);
});

it('skips rows that are not trashed in the count', function () {
	$apps = submitTrashableApplications(2);
	$apps->first()->delete(); // only one is trashed

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-restore', [
			'ids' => $apps->pluck('id')->all(),
		])
		->assertOk()
		// Only the trashed one is restored; the live one is a no-op.
		->assertJsonPath('restored', 1);

	expect(Application::onlyTrashed()->count())->toBe(0);
});

it('requires authentication', function () {
	$apps = submitTrashableApplications(1);
	$apps->each->delete();

	$this->postJson('/api/dashboard/applications/bulk-restore', [
		'ids' => $apps->pluck('id')->all(),
	])->assertUnauthorized();

	expect(Application::onlyTrashed()->count())->toBe(1);
});
