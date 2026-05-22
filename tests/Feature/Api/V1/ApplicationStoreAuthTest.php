<?php

use Illuminate\Support\Carbon;

beforeEach(function () {
	$this->rawKey = 'test-intake-key-'.bin2hex(random_bytes(8));
	config()->set('aporta.intake_api_key_hash', hash('sha256', $this->rawKey));
	Carbon::setTestNow('2026-05-22T10:30:00Z');
});

afterEach(function () {
	Carbon::setTestNow();
});

it('returns 401 when Authorization header is missing', function () {
	$this->postJson('/api/v1/applications', laafifFixture())
		->assertStatus(401);
});

it('returns 403 when the bearer key is wrong', function () {
	$this->postJson('/api/v1/applications', laafifFixture(), [
		'Authorization' => 'Bearer wrong-key',
	])->assertStatus(403);
});

it('accepts the request with the correct bearer key', function () {
	$this->postJson('/api/v1/applications', laafifFixture(), [
		'Authorization' => 'Bearer '.$this->rawKey,
	])->assertStatus(201);
});
