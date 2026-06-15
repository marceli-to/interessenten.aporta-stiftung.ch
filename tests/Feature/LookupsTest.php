<?php

use App\Enums\District;
use App\Enums\Nationality;
use App\Enums\Room;
use App\Enums\Status;

it('returns the full lookups payload', function () {
	$response = $this->getJson('/api/v1/lookups');

	$response->assertOk();

	foreach ([
		'statuses', 'salutations', 'marital_statuses', 'employment_statuses',
		'residence_permits', 'relationships', 'tenant_roles', 'districts',
		'floors', 'rooms', 'income_brackets', 'nationalities',
	] as $key) {
		expect($response->json())->toHaveKey($key);
	}
});

it('uses slugs not codes on the wire', function () {
	$response = $this->getJson('/api/v1/lookups');

	expect($response->json('statuses.0'))->toHaveKeys(['slug', 'label', 'sort_order', 'active'])
		->and($response->json('statuses.0'))->not->toHaveKey('code');
});

it('orders districts by sort_order', function () {
	$orders = collect($this->getJson('/api/v1/lookups')->json('districts'))
		->pluck('sort_order')
		->all();

	expect($orders)->toBe([4, 5, 6, 7, 8, 10]);
});

it('emits status, district and room slugs', function () {
	$response = $this->getJson('/api/v1/lookups');

	expect(collect($response->json('statuses'))->pluck('slug')->all())
		->toContain(Status::Opened->value, Status::Extended->value, Status::Archived->value, Status::Knif->value)
		->and(collect($response->json('districts'))->pluck('slug')->all())
		->toContain(District::Kreis4->value);
});

it('adds size to rooms', function () {
	$rooms = $this->getJson('/api/v1/lookups')->json('rooms');

	expect($rooms[0])->toHaveKey('size')
		->and((float) $rooms[0]['size'])->toBe(Room::Rooms2_0->size());
});

it('includes Switzerland in nationalities', function () {
	$slugs = collect($this->getJson('/api/v1/lookups')->json('nationalities'))
		->pluck('slug')
		->all();

	expect($slugs)->toContain(Nationality::CH->value, Nationality::DE->value);
});

it('sets ETag and cache headers', function () {
	$response = $this->getJson('/api/v1/lookups');

	expect($response->headers->get('ETag'))->not->toBeEmpty()
		->and($response->headers->get('Cache-Control'))->toContain('no-cache');
});

it('returns 304 when If-None-Match matches', function () {
	$first = $this->getJson('/api/v1/lookups');
	$etag = $first->headers->get('ETag');

	$second = $this->withHeaders(['If-None-Match' => $etag])->getJson('/api/v1/lookups');

	$second->assertStatus(304);
});
