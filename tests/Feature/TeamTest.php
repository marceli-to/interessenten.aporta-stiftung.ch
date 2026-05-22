<?php

use App\Models\TeamMember;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists all team members', function () {
    TeamMember::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->getJson('/api/dashboard/team')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a team member', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/team', [
            'firstname' => 'Anna',
            'name' => 'Müller',
        ])
        ->assertCreated()
        ->assertJsonPath('data.firstname', 'Anna')
        ->assertJsonPath('data.name', 'Müller');

    expect(TeamMember::count())->toBe(1);
});

it('validates required fields on create', function () {
    $this->actingAs($this->user)
        ->postJson('/api/dashboard/team', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['firstname', 'name']);
});

it('shows a single team member', function () {
    $member = TeamMember::factory()->create(['firstname' => 'Peter']);

    $this->actingAs($this->user)
        ->getJson("/api/dashboard/team/{$member->uuid}")
        ->assertOk()
        ->assertJsonPath('data.firstname', 'Peter');
});

it('updates a team member', function () {
    $member = TeamMember::factory()->create(['firstname' => 'Old']);

    $this->actingAs($this->user)
        ->putJson("/api/dashboard/team/{$member->uuid}", [
            'firstname' => 'New',
            'name' => $member->name,
        ])
        ->assertOk()
        ->assertJsonPath('data.firstname', 'New');

    expect($member->fresh()->firstname)->toBe('New');
});

it('toggles publish state', function () {
    $member = TeamMember::factory()->create(['publish' => false]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/team/{$member->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', true);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/team/{$member->uuid}/publish")
        ->assertOk()
        ->assertJsonPath('data.publish', false);
});

it('reorders team members', function () {
    $a = TeamMember::factory()->create(['sort_order' => 0]);
    $b = TeamMember::factory()->create(['sort_order' => 1]);

    $this->actingAs($this->user)
        ->patchJson('/api/dashboard/team/reorder', [
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
        ->patchJson('/api/dashboard/team/reorder', [
            'items' => [['uuid' => 'bad', 'sort_order' => 0]],
        ])
        ->assertUnprocessable();
});

it('requires authentication', function () {
    $this->getJson('/api/dashboard/team')->assertUnauthorized();
});
