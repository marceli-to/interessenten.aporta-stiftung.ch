<?php

use App\Actions\Application\Delete;
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

	$this->postJson('/api/v1/applications', laafifFixture(), [
		'Authorization' => 'Bearer '.$this->rawKey,
	])->assertStatus(201);

	$this->application = Application::first();
});

afterEach(function () {
	Carbon::setTestNow();
});

it('soft-deletes by default and preserves the aggregate', function () {
	(new Delete)->execute($this->application);

	expect(Application::find($this->application->id))->toBeNull();
	expect(Application::withTrashed()->find($this->application->id)->deleted_at)->not->toBeNull();

	// Aggregate untouched.
	expect(Applicant::where('application_id', $this->application->id)->count())->toBe(1);
	expect(Employer::count())->toBe(1);
	expect(CurrentHousing::count())->toBe(1);
	expect(StatusEvent::where('application_id', $this->application->id)->count())->toBe(1);
});

it('is a no-op on an already-soft-deleted application', function () {
	(new Delete)->execute($this->application);
	$originalTimestamp = Application::withTrashed()->find($this->application->id)->deleted_at;

	Carbon::setTestNow('2026-05-23T12:00:00Z');
	(new Delete)->execute(Application::withTrashed()->find($this->application->id));

	$current = Application::withTrashed()->find($this->application->id)->deleted_at;
	expect($current->toIso8601String())->toBe($originalTimestamp->toIso8601String());
});

it('force-deletes the application and cascades to the entire aggregate', function () {
	$applicationId = $this->application->id;

	(new Delete)->execute($this->application, force: true);

	expect(Application::withTrashed()->find($applicationId))->toBeNull();
	expect(Applicant::where('application_id', $applicationId)->count())->toBe(0);
	expect(Employer::count())->toBe(0);
	expect(CurrentHousing::count())->toBe(0);
	expect(Child::where('application_id', $applicationId)->count())->toBe(0);
	expect(StatusEvent::where('application_id', $applicationId)->count())->toBe(0);
	expect(DB::table('application_districts')->where('application_id', $applicationId)->count())->toBe(0);
	expect(DB::table('application_floors')->where('application_id', $applicationId)->count())->toBe(0);
	expect(DB::table('application_rooms')->where('application_id', $applicationId)->count())->toBe(0);
});

it('force-deletes an already-soft-deleted application', function () {
	$applicationId = $this->application->id;

	(new Delete)->execute($this->application);
	expect(Application::withTrashed()->find($applicationId))->not->toBeNull();

	(new Delete)->execute(Application::withTrashed()->find($this->application->id), force: true);
	expect(Application::withTrashed()->find($applicationId))->toBeNull();
	expect(Applicant::where('application_id', $applicationId)->count())->toBe(0);
});
