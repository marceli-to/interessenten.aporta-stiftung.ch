<?php

use App\Models\JobListing;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists all job listings', function () {
    JobListing::factory()->count(2)->create();

    $this->actingAs($this->user)
        ->getJson('/api/dashboard/jobs')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('creates a job listing', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/jobs', [
            'title' => 'Architekt 80%',
            'text' => 'Wir suchen eine engagierte Person.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Architekt 80%');

    expect(JobListing::count())->toBe(1);
});

it('validates required fields on create', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/jobs', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'text']);
});

it('shows a single job listing', function () {
    $job = JobListing::factory()->create(['title' => 'My Job']);

    $this->actingAs($this->user)
        ->getJson("/api/dashboard/jobs/{$job->uuid}")
        ->assertOk()
        ->assertJsonPath('data.title', 'My Job');
});

it('updates a job listing', function () {
    $job = JobListing::factory()->create(['title' => 'Old Title']);

    $this->actingAs($this->user)
        ->putJson("/api/dashboard/jobs/{$job->uuid}", [
            'title' => 'New Title',
            'text' => $job->text,
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'New Title');

    expect($job->fresh()->title)->toBe('New Title');
});

it('toggles publish state', function () {
    $job = JobListing::factory()->create(['publish' => false]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/jobs/{$job->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', true);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/jobs/{$job->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', false);
});

it('reorders job listings', function () {
    $a = JobListing::factory()->create(['sort_order' => 0]);
    $b = JobListing::factory()->create(['sort_order' => 1]);

    $this->actingAs($this->user)
        ->patchJson('/api/dashboard/jobs/reorder', [
            'items' => [
                ['uuid' => $a->uuid, 'sort_order' => 1],
                ['uuid' => $b->uuid, 'sort_order' => 0],
            ],
        ])
        ->assertOk();

    expect($a->fresh()->sort_order)->toBe(1)
        ->and($b->fresh()->sort_order)->toBe(0);
});

it('rejects reorder with invalid uuid', function () {
    $this->actingAs($this->user)
        ->patchJson('/api/dashboard/jobs/reorder', [
            'items' => [['uuid' => 'bad', 'sort_order' => 0]],
        ])
        ->assertUnprocessable();
});

it('deletes a job listing', function () {
    $job = JobListing::factory()->create();

    $this->actingAs($this->user)
        ->deleteJson("/api/dashboard/jobs/{$job->uuid}")
        ->assertNoContent();

    expect(JobListing::count())->toBe(0);
});

it('requires authentication', function () {
    $this->getJson('/api/dashboard/jobs')->assertUnauthorized();
});
