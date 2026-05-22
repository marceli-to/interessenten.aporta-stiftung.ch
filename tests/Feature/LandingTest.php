<?php

use App\Models\LandingSlide;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists all slides ordered by sort_order', function () {
    LandingSlide::factory()->create(['sort_order' => 1]);
    LandingSlide::factory()->create(['sort_order' => 0]);

    $this->actingAs($this->user)
        ->getJson('/api/dashboard/landing')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('creates an image slide', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/landing', ['type' => 'image'])
        ->assertCreated()
        ->assertJsonPath('data.type', 'image');
});

it('creates an image_text slide', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/landing', [
            'type' => 'image_text',
            'text' => 'Some overlay text',
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'image_text')
        ->assertJsonPath('data.text', 'Some overlay text');
});

it('requires text when type is image_text', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/landing', ['type' => 'image_text'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['text']);
});

it('validates type is required', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/landing', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('shows a single slide', function () {
    $slide = LandingSlide::factory()->create(['type' => 'image']);

    $this->actingAs($this->user)
        ->getJson("/api/dashboard/landing/{$slide->uuid}")
        ->assertOk()
        ->assertJsonPath('data.type', 'image');
});

it('updates a slide', function () {
    $slide = LandingSlide::factory()->imageOnly()->create();

    $this->actingAs($this->user)
        ->putJson("/api/dashboard/landing/{$slide->uuid}", [
            'type' => 'image_text',
            'text' => 'Updated text',
        ])
        ->assertOk()
        ->assertJsonPath('data.text', 'Updated text');

    expect($slide->fresh()->text)->toBe('Updated text');
});

it('toggles publish state', function () {
    $slide = LandingSlide::factory()->create(['publish' => false]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/landing/{$slide->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', true);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/landing/{$slide->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', false);
});

it('reorders slides', function () {
    $a = LandingSlide::factory()->create(['sort_order' => 0]);
    $b = LandingSlide::factory()->create(['sort_order' => 1]);

    $this->actingAs($this->user)
        ->patchJson('/api/dashboard/landing/reorder', [
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
        ->patchJson('/api/dashboard/landing/reorder', [
            'items' => [['uuid' => 'bad', 'sort_order' => 0]],
        ])
        ->assertUnprocessable();
});

it('deletes a slide', function () {
    $slide = LandingSlide::factory()->create();

    $this->actingAs($this->user)
        ->deleteJson("/api/dashboard/landing/{$slide->uuid}")
        ->assertNoContent();

    expect(LandingSlide::count())->toBe(0);
});

it('requires authentication', function () {
    $this->getJson('/api/dashboard/landing')->assertUnauthorized();
});
