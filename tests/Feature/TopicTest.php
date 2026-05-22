<?php

use App\Models\Topic;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists all topics ordered by sort_order', function () {
    Topic::factory()->create(['title' => 'Wohnbau', 'sort_order' => 1]);
    Topic::factory()->create(['title' => 'Gewerbe', 'sort_order' => 0]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/dashboard/topics')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect($response->json('data.0.title'))->toBe('Gewerbe')
        ->and($response->json('data.1.title'))->toBe('Wohnbau');
});

it('creates a topic and generates a slug', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/topics', ['title' => 'Offentliche Bauten'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Offentliche Bauten')
        ->assertJsonPath('data.slug', 'offentliche-bauten');

    expect(Topic::count())->toBe(1);
});

it('validates title is required on create', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/topics', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

it('shows a single topic', function () {
    $topic = Topic::factory()->create(['title' => 'Umbau']);

    $this->actingAs($this->user)
        ->getJson("/api/dashboard/topics/{$topic->uuid}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Umbau');
});

it('updates a topic and regenerates slug', function () {
    $topic = Topic::factory()->create(['title' => 'Old Title']);

    $this->actingAs($this->user)
        ->putJson("/api/dashboard/topics/{$topic->uuid}", ['title' => 'New Title'])
        ->assertOk()
        ->assertJsonPath('data.title', 'New Title')
        ->assertJsonPath('data.slug', 'new-title');

    expect($topic->fresh()->title)->toBe('New Title');
});

it('toggles publish state', function () {
    $topic = Topic::factory()->create(['publish' => false]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/topics/{$topic->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', true);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/topics/{$topic->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', false);
});

it('reorders topics', function () {
    $a = Topic::factory()->create(['sort_order' => 0]);
    $b = Topic::factory()->create(['sort_order' => 1]);

    $this->actingAs($this->user)
        ->patchJson('/api/dashboard/topics/reorder', [
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
        ->patchJson('/api/dashboard/topics/reorder', [
            'items' => [['uuid' => 'not-real', 'sort_order' => 0]],
        ])
        ->assertUnprocessable();
});

it('deletes a topic', function () {
    $topic = Topic::factory()->create();

    $this->actingAs($this->user)
        ->deleteJson("/api/dashboard/topics/{$topic->uuid}")
        ->assertNoContent();

    expect(Topic::count())->toBe(0);
});

it('requires authentication', function () {
    $this->getJson('/api/dashboard/topics')->assertUnauthorized();
});
