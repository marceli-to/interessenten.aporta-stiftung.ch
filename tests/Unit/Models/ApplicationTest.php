<?php

use App\Enums\Nationality;
use App\Enums\Status;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('builds a full aggregate via factory', function () {
	$application = Application::factory()->withFullAggregate()->create();

	expect($application->reference_number)->toBeGreaterThan(0)
		->and($application->status)->toBeInstanceOf(Status::class)
		->and($application->status)->toBe(Status::Opened)
		->and($application->applicants)->toHaveCount(1)
		->and($application->max_gross_rent)->not->toBeNull()
		->and($application->total_persons)->toBe(1);
});

it('casts nationality to enum', function () {
	$application = Application::factory()->withFullAggregate()->create();
	$applicant = $application->applicants->first();

	expect($applicant->nationality)->toBe(Nationality::CH);

	$applicant->update(['nationality' => Nationality::DE]);
	$applicant->refresh();

	expect($applicant->nationality)->toBe(Nationality::DE);
});
