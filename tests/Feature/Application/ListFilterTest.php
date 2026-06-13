<?php

use App\Enums\Status;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
	$this->user = User::factory()->create();
});

function listApplications(array $query = []): \Illuminate\Testing\TestResponse
{
	return test()->actingAs(test()->user)
		->getJson('/api/dashboard/applications?'.http_build_query($query));
}

function ids(\Illuminate\Testing\TestResponse $response): array
{
	return collect($response->json('data'))->pluck('id')->all();
}

it('filters by status', function () {
	$opened = Application::factory()->create(['status' => Status::Opened]);
	$archived = Application::factory()->create(['status' => Status::Archived]);

	$response = listApplications(['status' => 'archived'])->assertOk();

	expect(ids($response))->toBe([$archived->id]);
});

it('filters by max gross rent range', function () {
	$cheap = Application::factory()->create(['max_gross_rent' => '1200.00']);
	$mid = Application::factory()->create(['max_gross_rent' => '2000.00']);
	$pricey = Application::factory()->create(['max_gross_rent' => '3500.00']);

	$response = listApplications(['rent_min' => 1500, 'rent_max' => 2500])->assertOk();

	expect(ids($response))->toBe([$mid->id]);
});

it('filters by earliest move-in range', function () {
	$early = Application::factory()->create(['earliest_move_in' => '2026-07-01']);
	$inRange = Application::factory()->create(['earliest_move_in' => '2026-09-15']);
	$late = Application::factory()->create(['earliest_move_in' => '2026-12-01']);

	$response = listApplications(['move_in_from' => '2026-08-01', 'move_in_to' => '2026-10-31'])->assertOk();

	expect(ids($response))->toBe([$inRange->id]);
});

it('filters by any of the selected districts', function () {
	$k4 = Application::factory()->create();
	$k5 = Application::factory()->create();
	$k8 = Application::factory()->create();
	DB::table('application_districts')->insert([
		['application_id' => $k4->id, 'district_slug' => 'kreis_4'],
		['application_id' => $k5->id, 'district_slug' => 'kreis_5'],
		['application_id' => $k8->id, 'district_slug' => 'kreis_8'],
	]);

	$response = listApplications(['districts' => 'kreis_4,kreis_5'])->assertOk();

	expect(ids($response))->toEqualCanonicalizing([$k4->id, $k5->id]);
});

it('filters by any of the selected rooms', function () {
	$two = Application::factory()->create();
	$four = Application::factory()->create();
	DB::table('application_rooms')->insert([
		['application_id' => $two->id, 'room_slug' => 'rooms_2_0'],
		['application_id' => $four->id, 'room_slug' => 'rooms_4_0'],
	]);

	$response = listApplications(['rooms' => 'rooms_2_0'])->assertOk();

	expect(ids($response))->toBe([$two->id]);
});

it('combines filters', function () {
	$match = Application::factory()->create(['status' => Status::Opened, 'max_gross_rent' => '1800.00']);
	Application::factory()->create(['status' => Status::Archived, 'max_gross_rent' => '1800.00']);
	Application::factory()->create(['status' => Status::Opened, 'max_gross_rent' => '4000.00']);

	$response = listApplications(['status' => 'opened', 'rent_max' => 2000])->assertOk();

	expect(ids($response))->toBe([$match->id]);
});

it('returns total counts per status, ignoring the active filters', function () {
	Application::factory()->count(3)->create(['status' => Status::Opened]);
	Application::factory()->count(2)->create(['status' => Status::Archived]);

	// Even with a status filter narrowing the rows, the counts cover every bucket.
	$response = listApplications(['status' => 'opened'])->assertOk();

	expect($response->json('status_counts'))
		->toMatchArray(['opened' => 3, 'archived' => 2]);
	expect(ids($response))->toHaveCount(3);
});

it('ignores an unknown status value', function () {
	$opened = Application::factory()->create(['status' => Status::Opened]);
	Application::factory()->create(['status' => Status::Archived]);

	// A lone bogus value drops out, leaving no status filter -> all rows.
	expect(ids(listApplications(['status' => 'bogus'])->assertOk()))->toHaveCount(2);

	// Mixed with a valid value, only the valid one applies.
	expect(ids(listApplications(['status' => 'opened,bogus'])->assertOk()))
		->toBe([$opened->id]);
});
