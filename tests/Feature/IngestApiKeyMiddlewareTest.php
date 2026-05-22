<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
	$this->rawKey = 'test-ingest-key-'.bin2hex(random_bytes(8));
	config()->set('aporta.ingest_api_key_hash', hash('sha256', $this->rawKey));

	Route::middleware('auth.ingest')->post('/_test/ingest', fn () => response()->json(['ok' => true]));
});

it('accepts the correct bearer key', function () {
	$this->postJson('/_test/ingest', [], [
		'Authorization' => 'Bearer '.$this->rawKey,
	])->assertOk()->assertJson(['ok' => true]);
});

it('rejects a missing Authorization header with 401', function () {
	$this->postJson('/_test/ingest')->assertStatus(401);
});

it('rejects a malformed Authorization header with 401', function () {
	$this->postJson('/_test/ingest', [], [
		'Authorization' => 'NotBearer foo',
	])->assertStatus(401);
});

it('rejects a wrong bearer key with 403', function () {
	$this->postJson('/_test/ingest', [], [
		'Authorization' => 'Bearer wrong-key',
	])->assertStatus(403);
});
