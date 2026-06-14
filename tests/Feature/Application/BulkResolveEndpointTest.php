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

function submitResolvable(int $count): \Illuminate\Support\Collection
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

it('resolves explicit ids in the list order (id desc by default)', function () {
	$apps = submitResolvable(3);
	$idsDesc = $apps->pluck('id')->all(); // already id-desc

	$this->actingAs($this->user)
		// Pass them shuffled; the endpoint should return them in list order.
		->postJson('/api/dashboard/applications/bulk-resolve', [
			'ids' => array_reverse($idsDesc),
		])
		->assertOk()
		->assertExactJson(['ids' => $idsDesc]);
});

it('resolves an all-matching selection from the filter', function () {
	$apps = submitResolvable(3);
	$status = $apps->first()->status->value;

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-resolve', ['status' => $status])
		->assertOk()
		->assertJsonPath('ids', $apps->pluck('id')->all());
});

it('drops excluded ids in all-matching mode', function () {
	$apps = submitResolvable(3);
	$status = $apps->first()->status->value;
	$drop = $apps->first()->id;

	$expected = $apps->pluck('id')->reject(fn ($id) => $id === $drop)->values()->all();

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-resolve', [
			'status' => $status,
			'exclude' => [$drop],
		])
		->assertOk()
		->assertJsonPath('ids', $expected);
});

it('resolves trashed rows for an explicit selection (Gelöscht view)', function () {
	$apps = submitResolvable(2);
	$apps->each->delete();

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-resolve', [
			'ids' => $apps->pluck('id')->all(),
		])
		->assertOk()
		->assertJsonPath('ids', $apps->pluck('id')->all());
});

it('returns an empty list for an empty selection (read-only, no guard)', function () {
	submitResolvable(1);

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-resolve', [])
		->assertOk()
		->assertExactJson(['ids' => []]);
});

it('requires authentication', function () {
	$apps = submitResolvable(1);

	$this->postJson('/api/dashboard/applications/bulk-resolve', [
		'ids' => $apps->pluck('id')->all(),
	])->assertUnauthorized();
});
