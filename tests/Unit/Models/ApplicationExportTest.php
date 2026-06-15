<?php

use App\Enums\ExportStatus;
use App\Models\ApplicationExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('casts status to enum and expires_at to a date', function () {
	$export = ApplicationExport::factory()->ready()->create();

	expect($export->status)->toBeInstanceOf(ExportStatus::class)
		->and($export->status)->toBe(ExportStatus::Ready)
		->and($export->expires_at)->not->toBeNull()
		->and($export->expires_at->isFuture())->toBeTrue();
});

it('belongs to a user', function () {
	$user = User::factory()->create();
	$export = ApplicationExport::factory()->for($user)->create();

	expect($export->user)->toBeInstanceOf(User::class)
		->and($export->user->id)->toBe($user->id);
});

it('is downloadable only when ready, has a path and is not expired', function () {
	expect(ApplicationExport::factory()->ready()->create()->isDownloadable())->toBeTrue();
	expect(ApplicationExport::factory()->create()->isDownloadable())->toBeFalse();        // pending
	expect(ApplicationExport::factory()->failed()->create()->isDownloadable())->toBeFalse();
	expect(ApplicationExport::factory()->expired()->create()->isDownloadable())->toBeFalse();
});

it('reports expiry correctly', function () {
	expect(ApplicationExport::factory()->ready()->create()->isExpired())->toBeFalse();
	expect(ApplicationExport::factory()->expired()->create()->isExpired())->toBeTrue();
	expect(ApplicationExport::factory()->create(['expires_at' => null])->isExpired())->toBeFalse();
});
