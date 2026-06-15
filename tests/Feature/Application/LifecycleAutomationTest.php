<?php

use App\Enums\Status;
use App\Models\Application;
use Illuminate\Support\Carbon;

beforeEach(function () {
	Carbon::setTestNow('2026-06-15T03:00:00Z');
});

afterEach(function () {
	Carbon::setTestNow();
});

/* ---------------------------------------------------------------- archiving */

it('archives an opened application past the 9 month deadline', function () {
	$app = Application::factory()->create([
		'status' => Status::Opened,
		'opened_at' => now()->subMonths(9)->subDay(),
		'extended_at' => null,
	]);

	$this->artisan('app:archive-stale')->assertSuccessful();

	$app->refresh();
	expect($app->status)->toBe(Status::Archived);
	expect($app->archived_at->toDateString())->toBe(now()->toDateString());
});

it('leaves an opened application that is still within the deadline', function () {
	$app = Application::factory()->create([
		'status' => Status::Opened,
		'opened_at' => now()->subMonths(9)->addDay(),
	]);

	$this->artisan('app:archive-stale')->assertSuccessful();

	expect($app->refresh()->status)->toBe(Status::Opened);
});

it('uses extended_at, not opened_at, as the deadline reference when set', function () {
	// Opened long ago, but extended recently → still within the window.
	$fresh = Application::factory()->create([
		'status' => Status::Extended,
		'opened_at' => now()->subYears(2),
		'extended_at' => now()->subMonths(1),
	]);

	// Opened long ago and extended long ago → past the window.
	$stale = Application::factory()->create([
		'status' => Status::Extended,
		'opened_at' => now()->subYears(2),
		'extended_at' => now()->subMonths(10),
	]);

	$this->artisan('app:archive-stale')->assertSuccessful();

	expect($fresh->refresh()->status)->toBe(Status::Extended);
	expect($stale->refresh()->status)->toBe(Status::Archived);
});

it('does not touch already-archived or KNIF applications', function () {
	$archived = Application::factory()->create([
		'status' => Status::Archived,
		'opened_at' => now()->subYears(2),
		'archived_at' => now()->subMonths(1),
	]);
	$knif = Application::factory()->create([
		'status' => Status::Knif,
		'opened_at' => now()->subYears(2),
	]);

	$this->artisan('app:archive-stale')->assertSuccessful();

	expect($archived->refresh()->status)->toBe(Status::Archived);
	// archived_at must stay untouched (no re-stamp).
	expect($archived->archived_at->toDateString())->toBe(now()->subMonths(1)->toDateString());
	expect($knif->refresh()->status)->toBe(Status::Knif);
});

it('records a system status event for each auto-archive', function () {
	$app = Application::factory()->create([
		'status' => Status::Opened,
		'opened_at' => now()->subYear(),
	]);

	$this->artisan('app:archive-stale')->assertSuccessful();

	$event = $app->statusEvents()->latest('id')->first();
	expect($event)->not->toBeNull();
	expect($event->from_status)->toBe(Status::Opened);
	expect($event->to_status)->toBe(Status::Archived);
	expect($event->actor_user_id)->toBeNull();
	expect($event->reason)->toBe('Automatisch archiviert (Frist abgelaufen)');
});

/* ----------------------------------------------------------------- deleting */

it('soft-deletes an archived application past the 3 month retention window', function () {
	$app = Application::factory()->create([
		'status' => Status::Archived,
		'opened_at' => now()->subYears(2),
		'archived_at' => now()->subMonths(3)->subDay(),
	]);

	$this->artisan('app:delete-archived')->assertSuccessful();

	expect(Application::whereKey($app->id)->exists())->toBeFalse();
	expect(Application::withTrashed()->whereKey($app->id)->whereNotNull('deleted_at')->exists())->toBeTrue();
});

it('keeps an archived application still inside the retention window', function () {
	$app = Application::factory()->create([
		'status' => Status::Archived,
		'archived_at' => now()->subMonths(3)->addDay(),
	]);

	$this->artisan('app:delete-archived')->assertSuccessful();

	expect(Application::whereKey($app->id)->exists())->toBeTrue();
});

it('skips archived applications without an archived_at date', function () {
	$app = Application::factory()->create([
		'status' => Status::Archived,
		'archived_at' => null,
	]);

	$this->artisan('app:delete-archived')->assertSuccessful();

	expect(Application::whereKey($app->id)->exists())->toBeTrue();
});

it('does not delete open or extended applications regardless of age', function () {
	$opened = Application::factory()->create([
		'status' => Status::Opened,
		'opened_at' => now()->subYears(5),
	]);
	$extended = Application::factory()->create([
		'status' => Status::Extended,
		'opened_at' => now()->subYears(5),
		'extended_at' => now()->subYears(5),
	]);

	$this->artisan('app:delete-archived')->assertSuccessful();

	expect(Application::whereKey($opened->id)->exists())->toBeTrue();
	expect(Application::whereKey($extended->id)->exists())->toBeTrue();
});
