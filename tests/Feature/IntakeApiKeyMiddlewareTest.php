<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
	$this->rawKey = 'test-intake-key-'.bin2hex(random_bytes(8));
	config()->set('aporta.intake_api_key_hash', hash('sha256', $this->rawKey));

	Route::middleware('auth.intake')->post('/_test/intake', fn () => response()->json(['ok' => true]));
});

it('accepts the correct bearer key', function () {
	$this->postJson('/_test/intake', [], [
		'Authorization' => 'Bearer '.$this->rawKey,
	])->assertOk()->assertJson(['ok' => true]);
});

it('rejects a missing Authorization header with 401', function () {
	$this->postJson('/_test/intake')->assertStatus(401);
});

it('rejects a malformed Authorization header with 401', function () {
	$this->postJson('/_test/intake', [], [
		'Authorization' => 'NotBearer foo',
	])->assertStatus(401);
});

it('rejects a wrong bearer key with 403', function () {
	$this->postJson('/_test/intake', [], [
		'Authorization' => 'Bearer wrong-key',
	])->assertStatus(403);
});
