<?php

use App\Models\AtelierPage;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists all atelier pages', function () {
    AtelierPage::factory()->count(2)->create();

    $this->actingAs($this->user)
        ->getJson('/api/dashboard/atelier')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('shows a single atelier page', function () {
    $page = AtelierPage::factory()->create(['title' => 'Über uns']);

    $this->actingAs($this->user)
        ->getJson("/api/dashboard/atelier/{$page->uuid}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Über uns');
});

it('updates an atelier page', function () {
    $page = AtelierPage::factory()->create(['slug' => 'team', 'title' => 'Old']);

    $this->actingAs($this->user)
        ->putJson("/api/dashboard/atelier/{$page->uuid}", [
            'title' => 'New Title',
            'text' => 'Some text',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'New Title');

    expect($page->fresh()->title)->toBe('New Title');
});

it('accepts an empty update payload', function () {
    $page = AtelierPage::factory()->create(['slug' => 'team']);

    $this->actingAs($this->user)
        ->putJson("/api/dashboard/atelier/{$page->uuid}", [])
        ->assertOk();
});

it('toggles publish state', function () {
    $page = AtelierPage::factory()->create(['publish' => false]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/atelier/{$page->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', true);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/atelier/{$page->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', false);
});

it('does not expose sort_order or meta_description in response', function () {
    $page = AtelierPage::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/dashboard/atelier/{$page->uuid}")
        ->assertOk();

    expect($response->json('data'))->not->toHaveKey('sort_order')
        ->and($response->json('data'))->not->toHaveKey('meta_description');
});

it('requires authentication', function () {
    $this->getJson('/api/dashboard/atelier')->assertUnauthorized();
});
