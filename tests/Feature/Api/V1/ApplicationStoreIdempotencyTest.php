<?php

use App\Jobs\NotifyNewApplication;
use App\Models\Application;
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

it('returns 200 with the original reference on a retry with the same submission_id', function () {
	Bus::fake([NotifyNewApplication::class]);

	$headers = ['Authorization' => 'Bearer '.$this->rawKey];

	$first = $this->postJson('/api/v1/applications', laafifFixture(), $headers)->assertStatus(201);
	$ref = $first->json('data.reference_number');

	$second = $this->postJson('/api/v1/applications', laafifFixture(), $headers)->assertStatus(200);

	expect($second->json('data.reference_number'))->toBe($ref);
	expect(Application::count())->toBe(1);

	Bus::assertDispatchedTimes(NotifyNewApplication::class, 1);
});

it('treats different submission_ids with identical payload as distinct applications', function () {
	$headers = ['Authorization' => 'Bearer '.$this->rawKey];

	$a = laafifFixture();
	$b = laafifFixture();
	$b['submission_id'] = '01HZN5XK9R8E3Q4G7P2W6F0VAC';

	$this->postJson('/api/v1/applications', $a, $headers)->assertStatus(201);
	$this->postJson('/api/v1/applications', $b, $headers)->assertStatus(201);

	expect(Application::count())->toBe(2);
});
