<?php

use App\Models\Project;
use App\Models\Topic;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists all projects', function () {
    Project::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->getJson('/api/dashboard/projects')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a project and generates slug from title, location, and year', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/projects', [
            'title' => 'New Project',
            'location' => 'Zurich',
            'year' => 2024,
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'New Project')
        ->assertJsonPath('data.slug', 'new-project-zurich-2024');

    expect(Project::count())->toBe(1);
});

it('validates required fields on create', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/projects', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'year']);
});

it('shows a single project', function () {
    $project = Project::factory()->create(['title' => 'My Project']);

    $this->actingAs($this->user)
        ->getJson("/api/dashboard/projects/{$project->uuid}")
        ->assertOk()
        ->assertJsonPath('data.title', 'My Project');
});

it('updates a project', function () {
    $project = Project::factory()->create();

    $this->actingAs($this->user)
        ->putJson("/api/dashboard/projects/{$project->uuid}", [
            'title' => 'Updated Title',
            'location' => 'Zurich',
            'year' => 2020,
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');

    expect($project->fresh()->title)->toBe('Updated Title');
});

it('attaches a topic on create', function () {
    $topic = Topic::factory()->create();

    $response = $this->actingAs($this->user)
        ->postJson('/api/dashboard/projects', [
            'title' => 'Project with Topic',
            'location' => 'Zurich',
            'year' => 2022,
            'topic_id' => $topic->uuid,
        ])
        ->assertCreated();

    expect($response->json('data.topic.uuid'))->toBe($topic->uuid);
});

it('rejects an invalid topic_id', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/projects', [
            'title' => 'Project',
            'year' => 2022,
            'topic_id' => 'non-existent-uuid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['topic_id']);
});

it('toggles publish state', function () {
    $project = Project::factory()->create(['publish' => false]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/projects/{$project->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', true);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/projects/{$project->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', false);
});

it('toggles feature state', function () {
    $project = Project::factory()->create(['feature' => false]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/projects/{$project->uuid}/feature")
        ->assertOk()
        ->assertJsonPath('data.feature', true);
});

it('lists projects ordered by year DESC', function () {
    $older = Project::factory()->create(['year' => 2018]);
    $newer = Project::factory()->create(['year' => 2024]);
    $middle = Project::factory()->create(['year' => 2020]);

    $this->actingAs($this->user)
        ->getJson('/api/dashboard/projects')
        ->assertOk()
        ->assertJsonPath('data.0.uuid', (string) $newer->uuid)
        ->assertJsonPath('data.1.uuid', (string) $middle->uuid)
        ->assertJsonPath('data.2.uuid', (string) $older->uuid);
});

it('deletes a project', function () {
    $project = Project::factory()->create();

    $this->actingAs($this->user)
        ->deleteJson("/api/dashboard/projects/{$project->uuid}")
        ->assertNoContent();

    expect(Project::count())->toBe(0);
});

it('requires authentication', function () {
    $this->getJson('/api/dashboard/projects')->assertUnauthorized();
});
