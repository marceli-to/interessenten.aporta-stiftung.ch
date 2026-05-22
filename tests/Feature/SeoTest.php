<?php

use App\Models\SeoSetting;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->seo = SeoSetting::factory()->create();
});

it('shows the seo settings', function () {
    $this->actingAs($this->user)
        ->getJson('/api/dashboard/seo')
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'uuid',
            'landing_meta_description',
            'projects_meta_description',
            'werkliste_meta_description',
            'profile_meta_description',
            'team_meta_description',
            'jobs_meta_description',
            'contact_meta_description',
        ]]);
});

it('updates seo meta descriptions', function () {
    $this->actingAs($this->user)
        ->putJson('/api/dashboard/seo', [
            'landing_meta_description' => 'Architekturbüro in Zürich',
            'projects_meta_description' => 'Unsere Projekte',
        ])
        ->assertOk()
        ->assertJsonPath('data.landing_meta_description', 'Architekturbüro in Zürich');

    expect($this->seo->fresh()->landing_meta_description)->toBe('Architekturbüro in Zürich');
});

it('accepts all fields as nullable', function () {
    $this->actingAs($this->user)
        ->putJson('/api/dashboard/seo', [])
        ->assertOk();
});

it('enforces max 500 chars on meta descriptions', function () {
    $this->actingAs($this->user)
        ->putJson('/api/dashboard/seo', [
            'landing_meta_description' => str_repeat('a', 501),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['landing_meta_description']);
});

it('requires authentication', function () {
    $this->getJson('/api/dashboard/seo')->assertUnauthorized();
});
