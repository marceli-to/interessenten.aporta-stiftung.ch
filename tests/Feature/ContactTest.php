<?php

use App\Models\Contact;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->contact = Contact::factory()->create();
});

it('shows the contact record', function () {
    $this->actingAs($this->user)
        ->getJson('/api/dashboard/contact')
        ->assertOk()
        ->assertJsonPath('data.name', $this->contact->name)
        ->assertJsonPath('data.email', $this->contact->email);
});

it('does not expose meta_description in response', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/dashboard/contact')
        ->assertOk();

    expect($response->json('data'))->not->toHaveKey('meta_description');
});

it('updates the contact record', function () {
    $this->actingAs($this->user)
        ->putJson('/api/dashboard/contact', [
            'name' => 'Updated Name GmbH',
            'address' => 'Musterstrasse 1, 8000 Zürich',
            'email' => 'info@example.com',
            'phone' => '+41 44 000 00 00',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name GmbH');

    expect($this->contact->fresh()->name)->toBe('Updated Name GmbH');
});

it('validates required fields on update', function () {
    $this->actingAs($this->user)
        ->putJson('/api/dashboard/contact', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'address', 'email', 'phone']);
});

it('validates email format', function () {
    $this->actingAs($this->user)
        ->putJson('/api/dashboard/contact', [
            'name' => 'Test',
            'address' => 'Test',
            'email' => 'not-an-email',
            'phone' => '123',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('validates maps_url must be a url when provided', function () {
    $this->actingAs($this->user)
        ->putJson('/api/dashboard/contact', [
            'name' => 'Test',
            'address' => 'Test',
            'email' => 'test@example.com',
            'phone' => '123',
            'maps_url' => 'not-a-url',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['maps_url']);
});

it('requires authentication', function () {
    $this->getJson('/api/dashboard/contact')->assertUnauthorized();
});
