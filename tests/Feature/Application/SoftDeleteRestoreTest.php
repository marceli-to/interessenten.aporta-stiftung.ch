<?php

use App\Jobs\NotifyNewApplication;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Child;
use App\Models\CurrentHousing;
use App\Models\Employer;
use App\Models\StatusEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
	$this->rawKey = 'test-intake-key-'.bin2hex(random_bytes(8));
	config()->set('aporta.intake_api_key_hash', hash('sha256', $this->rawKey));
	Carbon::setTestNow('2026-05-22T10:30:00Z');
	Bus::fake([NotifyNewApplication::class]);
});

afterEach(function () {
	Carbon::setTestNow();
});

/**
 * Locks in the invariant that soft-deleting an Application leaves the entire
 * aggregate (applicants, housing, employer, children, status_events, pivots)
 * untouched at the DB level, so restoring brings everything back identically.
 *
 * If this test ever fails, the most likely cause is that SoftDeletes was added
 * to one of the child models without also handling restore for it — or that
 * something started doing a real DELETE on a child row during soft-delete.
 */
it('soft-deletes and restores an application with the full aggregate intact', function () {
	$this->postJson('/api/v1/applications', laafifFixture(), [
		'Authorization' => 'Bearer '.$this->rawKey,
	])->assertStatus(201);

	$application = Application::first();
	$applicationId = $application->id;

	// Snapshot the aggregate BEFORE deletion so we can compare after restore.
	$before = [
		'application' => $application->only([
			'id', 'reference_number', 'status', 'submission_id',
			'shares_apartment', 'max_gross_rent', 'total_persons',
			'plays_music', 'has_pets',
		]),
		'applicants' => Applicant::where('application_id', $applicationId)->orderBy('position')->get()->toArray(),
		'employers' => Employer::whereIn('applicant_id', Applicant::where('application_id', $applicationId)->pluck('id'))->get()->toArray(),
		'current_housings' => CurrentHousing::whereIn('applicant_id', Applicant::where('application_id', $applicationId)->pluck('id'))->get()->toArray(),
		'children' => Child::where('application_id', $applicationId)->orderBy('position')->get()->toArray(),
		'status_events' => StatusEvent::where('application_id', $applicationId)->orderBy('id')->get()->toArray(),
		'districts' => DB::table('application_districts')->where('application_id', $applicationId)->orderBy('district_slug')->pluck('district_slug')->all(),
		'floors' => DB::table('application_floors')->where('application_id', $applicationId)->orderBy('floor_slug')->pluck('floor_slug')->all(),
		'rooms' => DB::table('application_rooms')->where('application_id', $applicationId)->orderBy('room_slug')->pluck('room_slug')->all(),
	];

	// --- soft delete ---
	$application->delete();

	// Row is hidden from default queries but the column-level state is still there.
	expect(Application::find($applicationId))->toBeNull();
	$soft = Application::withTrashed()->find($applicationId);
	expect($soft)->not->toBeNull();
	expect($soft->deleted_at)->not->toBeNull();

	// Critical invariant: no DB-level cascade fired. Every child table still has its rows.
	expect(Applicant::where('application_id', $applicationId)->count())->toBe(count($before['applicants']));
	expect(Employer::whereIn('applicant_id', Applicant::where('application_id', $applicationId)->pluck('id'))->count())->toBe(count($before['employers']));
	expect(CurrentHousing::whereIn('applicant_id', Applicant::where('application_id', $applicationId)->pluck('id'))->count())->toBe(count($before['current_housings']));
	expect(Child::where('application_id', $applicationId)->count())->toBe(count($before['children']));
	expect(StatusEvent::where('application_id', $applicationId)->count())->toBe(count($before['status_events']));
	expect(DB::table('application_districts')->where('application_id', $applicationId)->count())->toBe(count($before['districts']));
	expect(DB::table('application_floors')->where('application_id', $applicationId)->count())->toBe(count($before['floors']));
	expect(DB::table('application_rooms')->where('application_id', $applicationId)->count())->toBe(count($before['rooms']));

	// --- restore ---
	$soft->restore();

	$restored = Application::find($applicationId);
	expect($restored)->not->toBeNull();
	expect($restored->deleted_at)->toBeNull();

	// Same scalar attributes on the parent.
	expect($restored->only([
		'id', 'reference_number', 'status', 'submission_id',
		'shares_apartment', 'max_gross_rent', 'total_persons',
		'plays_music', 'has_pets',
	]))->toEqual($before['application']);

	// Same aggregate, row-for-row.
	$after = [
		'applicants' => Applicant::where('application_id', $applicationId)->orderBy('position')->get()->toArray(),
		'employers' => Employer::whereIn('applicant_id', Applicant::where('application_id', $applicationId)->pluck('id'))->get()->toArray(),
		'current_housings' => CurrentHousing::whereIn('applicant_id', Applicant::where('application_id', $applicationId)->pluck('id'))->get()->toArray(),
		'children' => Child::where('application_id', $applicationId)->orderBy('position')->get()->toArray(),
		'status_events' => StatusEvent::where('application_id', $applicationId)->orderBy('id')->get()->toArray(),
		'districts' => DB::table('application_districts')->where('application_id', $applicationId)->orderBy('district_slug')->pluck('district_slug')->all(),
		'floors' => DB::table('application_floors')->where('application_id', $applicationId)->orderBy('floor_slug')->pluck('floor_slug')->all(),
		'rooms' => DB::table('application_rooms')->where('application_id', $applicationId)->orderBy('room_slug')->pluck('room_slug')->all(),
	];

	expect($after['applicants'])->toEqual($before['applicants']);
	expect($after['employers'])->toEqual($before['employers']);
	expect($after['current_housings'])->toEqual($before['current_housings']);
	expect($after['children'])->toEqual($before['children']);
	expect($after['status_events'])->toEqual($before['status_events']);
	expect($after['districts'])->toEqual($before['districts']);
	expect($after['floors'])->toEqual($before['floors']);
	expect($after['rooms'])->toEqual($before['rooms']);
});

it('preserves a co-applicant and their children across soft-delete and restore', function () {
	$data = laafifFixture();

	// Add a co-applicant + two children to widen the aggregate the test exercises.
	$data['co_applicant'] = [
		'salutation' => 'frau',
		'first_name' => 'Jane',
		'last_name' => 'Doe',
		'birth_date' => '1995-03-12',
		'marital_status' => 'single',
		'nationality' => 'CH',
		'place_of_origin' => 'Bern',
		'residence_permit' => null,
		'swiss_residence_since' => null,
		'mobile_phone' => '+41791234567',
		'landline_phone' => null,
		'email' => 'jane.doe@example.com',
		'occupation' => 'Designer',
		'employment_status' => 'employed',
		'debt_enforcement_last_2y' => false,
		'employer' => [
			'name' => 'Acme AG',
			'workload_percent' => 80,
			'annual_income_bracket' => '50k_60k',
		],
		'current_housing' => [
			'tenant_role' => 'main_tenant',
			'terminated_by_landlord' => false,
			'termination_reason' => null,
			'landlord_name' => 'Acme Properties',
			'landlord_contact_person' => null,
			'landlord_phone' => null,
		],
		'relationship_to_main' => 'life_partner',
		'same_address_as_main' => true,
	];
	$data['household_info']['total_persons'] = 4;
	$data['household_info']['adults_count'] = 2;
	$data['household_info']['children_count'] = 2;
	$data['children'] = [
		['position' => 1, 'birth_year' => 2019],
		['position' => 2, 'birth_year' => 2022],
	];

	$this->postJson('/api/v1/applications', $data, [
		'Authorization' => 'Bearer '.$this->rawKey,
	])->assertStatus(201);

	$application = Application::first();
	expect(Applicant::where('application_id', $application->id)->count())->toBe(2);
	expect(Child::where('application_id', $application->id)->count())->toBe(2);

	$application->delete();

	// After soft delete: both applicants, both children, both employers, both current_housings still on disk.
	$applicantIds = Applicant::where('application_id', $application->id)->pluck('id');
	expect($applicantIds->count())->toBe(2);
	expect(Employer::whereIn('applicant_id', $applicantIds)->count())->toBe(2);
	expect(CurrentHousing::whereIn('applicant_id', $applicantIds)->count())->toBe(2);
	expect(Child::where('application_id', $application->id)->count())->toBe(2);

	Application::withTrashed()->find($application->id)->restore();

	expect(Application::find($application->id))->not->toBeNull();
	expect(Applicant::where('application_id', $application->id)->count())->toBe(2);
	expect(Child::where('application_id', $application->id)->count())->toBe(2);
});
