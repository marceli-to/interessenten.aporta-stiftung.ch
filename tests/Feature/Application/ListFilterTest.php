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

/**
 * Build an application whose main applicant earns the given income bracket. An
 * optional co-applicant bracket lets a test prove the filter ignores co-income.
 */
function applicationWithIncome(string $mainBracket, ?string $coBracket = null): Application
{
	$application = Application::factory()->create();

	$main = \App\Models\Applicant::factory()->mainApplicant()->create(['application_id' => $application->id]);
	\App\Models\Employer::factory()->create(['applicant_id' => $main->id, 'annual_income_bracket_slug' => $mainBracket]);

	if ($coBracket !== null) {
		$co = \App\Models\Applicant::factory()->coApplicant()->create(['application_id' => $application->id]);
		\App\Models\Employer::factory()->create(['applicant_id' => $co->id, 'annual_income_bracket_slug' => $coBracket]);
	}

	return $application;
}

it('filters by main applicant income bracket range', function () {
	$low = applicationWithIncome('20k_30k');
	$mid = applicationWithIncome('60k_70k');
	$upper = applicationWithIncome('80k_90k');
	$high = applicationWithIncome('120k_140k');

	$response = listApplications(['income_min' => '60k_70k', 'income_max' => '80k_90k'])->assertOk();

	expect(ids($response))->toEqualCanonicalizing([$mid->id, $upper->id]);
});

it('treats an open income bound as unbounded on that side', function () {
	$low = applicationWithIncome('less_than_20k');
	$mid = applicationWithIncome('40k_50k');
	$high = applicationWithIncome('90k_100k');

	// Only a lower bound -> everything from 40k_50k upwards.
	$response = listApplications(['income_min' => '40k_50k'])->assertOk();

	expect(ids($response))->toEqualCanonicalizing([$mid->id, $high->id]);
});

it('matches income on the main applicant only, never the co-applicant', function () {
	// Main is out of range, co-applicant is in range -> must NOT match.
	$coInRange = applicationWithIncome('20k_30k', coBracket: '70k_80k');
	// Main is in range -> matches.
	$mainInRange = applicationWithIncome('70k_80k');

	$response = listApplications(['income_min' => '60k_70k', 'income_max' => '80k_90k'])->assertOk();

	expect(ids($response))->toBe([$mainInRange->id]);
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
