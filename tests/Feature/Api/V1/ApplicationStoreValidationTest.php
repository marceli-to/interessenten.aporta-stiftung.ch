<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
	$this->rawKey = 'test-intake-key-'.bin2hex(random_bytes(8));
	config()->set('aporta.intake_api_key_hash', hash('sha256', $this->rawKey));
	Carbon::setTestNow('2026-05-22T10:30:00Z');
	$this->headers = ['Authorization' => 'Bearer '.$this->rawKey];
});

afterEach(function () {
	Carbon::setTestNow();
});

function submit(array $data, array $headers): \Illuminate\Testing\TestResponse
{
	return test()->postJson('/api/v1/applications', $data, $headers);
}

it('rejects an invalid submission_id', function () {
	$data = laafifFixture();
	$data['submission_id'] = 'not-a-ulid';

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['submission_id']);
});

it('rejects a future submitted_at beyond skew', function () {
	$data = laafifFixture();
	$data['submitted_meta']['submitted_at'] = '2026-05-22T11:00:00Z';

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['submitted_meta.submitted_at']);
});

it('rejects an invalid IP', function () {
	$data = laafifFixture();
	$data['submitted_meta']['ip'] = 'definitely-not-an-ip';

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['submitted_meta.ip']);
});

it('rejects an unknown nationality slug', function () {
	$data = laafifFixture();
	$data['main_applicant']['nationality'] = 'XX';

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['main_applicant.nationality']);
});

it('requires place_of_origin when nationality is CH', function () {
	$data = laafifFixture();
	$data['main_applicant']['nationality'] = 'CH';
	$data['main_applicant']['place_of_origin'] = null;

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['main_applicant.place_of_origin']);
});

it('requires residence_permit and swiss_residence_since when nationality is not CH', function () {
	$data = laafifFixture();
	$data['main_applicant']['nationality'] = 'DE';
	$data['main_applicant']['place_of_origin'] = null;
	$data['main_applicant']['residence_permit'] = null;
	$data['main_applicant']['swiss_residence_since'] = null;

	submit($data, $this->headers)
		->assertStatus(422)
		->assertJsonValidationErrors(['main_applicant.residence_permit', 'main_applicant.swiss_residence_since']);
});

it('requires employer when employment_status is employed', function () {
	$data = laafifFixture();
	$data['main_applicant']['employer'] = null;

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['main_applicant.employer']);
});

it('requires termination_reason when terminated_by_landlord is true', function () {
	$data = laafifFixture();
	$data['main_applicant']['current_housing']['terminated_by_landlord'] = true;
	$data['main_applicant']['current_housing']['termination_reason'] = null;

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['main_applicant.current_housing.termination_reason']);
});

it('requires previous_landlord when rent_duration is less_than_1_year', function () {
	$data = laafifFixture();
	$data['main_applicant']['current_housing']['rent_duration'] = 'less_than_1_year';
	$data['main_applicant']['current_housing']['previous_landlord'] = null;

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['main_applicant.current_housing.previous_landlord']);
});

it('requires musical_instruments when plays_music is true', function () {
	$data = laafifFixture();
	$data['household_info']['plays_music'] = true;
	$data['household_info']['musical_instruments'] = null;

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['household_info.musical_instruments']);
});

it('requires pets_description when has_pets is true', function () {
	$data = laafifFixture();
	$data['household_info']['has_pets'] = true;
	$data['household_info']['pets_description'] = null;

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['household_info.pets_description']);
});

it('rejects children count mismatch', function () {
	$data = laafifFixture();
	$data['household_info']['total_persons'] = 2;
	$data['household_info']['adults_count'] = 1;
	$data['household_info']['children_count'] = 1;
	$data['children'] = [];

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['children']);
});

it('rejects when total_persons != adults_count + children_count', function () {
	$data = laafifFixture();
	$data['household_info']['total_persons'] = 5;
	$data['household_info']['adults_count'] = 1;
	$data['household_info']['children_count'] = 0;

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['household_info.total_persons']);
});

it('rejects adults_count below 1', function () {
	$data = laafifFixture();
	$data['household_info']['adults_count'] = 0;
	$data['household_info']['total_persons'] = 0;

	submit($data, $this->headers)->assertStatus(422)->assertJsonValidationErrors(['household_info.adults_count']);
});

it('requires at least one district and floor', function () {
	$data = laafifFixture();
	$data['housing_wish']['districts'] = [];
	$data['housing_wish']['floors'] = [];

	submit($data, $this->headers)
		->assertStatus(422)
		->assertJsonValidationErrors(['housing_wish.districts', 'housing_wish.floors']);
});

it('derives the room range from the household size, ignoring any submitted rooms', function () {
	$data = laafifFixture();
	// Household of 3 → eligible rooms are persons ± 1 = 2, 3, 4.
	$data['household_info']['adults_count'] = 2;
	$data['household_info']['children_count'] = 1;
	$data['household_info']['total_persons'] = 3;
	$data['children'] = [['position' => 1, 'birth_year' => 2020]];
	// A bogus rooms value in the payload must be ignored — rooms is derived.
	$data['housing_wish']['rooms'] = ['rooms_5_0'];

	submit($data, $this->headers)->assertStatus(201);

	$application = \App\Models\Application::latest('id')->first();
	$rooms = DB::table('application_rooms')->where('application_id', $application->id)->pluck('room_slug')->sort()->values()->all();

	expect($rooms)->toBe(['rooms_2_0', 'rooms_3_0', 'rooms_4_0']);
});

it('requires co-applicant address fields when same_address_as_main is false', function () {
	$data = laafifFixture();
	$data['shares_apartment'] = true;
	$data['co_applicant'] = [
		'relationship_to_main' => 'life_partner',
		'same_address_as_main' => false,
		'salutation' => 'frau',
		'first_name' => 'Mia',
		'last_name' => 'Muster',
		'birth_date' => '1995-04-10',
		'marital_status' => 'single',
		'nationality' => 'CH',
		'place_of_origin' => 'Bern',
		'mobile_phone' => '+41761234567',
		'email' => 'mia@example.com',
		'occupation' => 'Lehrerin',
		'employment_status' => 'employed',
		'debt_enforcement_last_2y' => false,
		'employer' => [
			'name' => 'Schule',
			'workload_percent' => 60,
			'annual_income_bracket' => '50k_60k',
		],
		'current_housing' => [
			'tenant_role' => 'main_tenant',
			'terminated_by_landlord' => false,
			'termination_reason' => null,
			'landlord_name' => 'Foo',
			'rent_duration' => 'more_than_2_years',
		],
	];

	submit($data, $this->headers)
		->assertStatus(422)
		->assertJsonValidationErrors([
			'co_applicant.street',
			'co_applicant.postal_code',
			'co_applicant.city',
		]);
});
