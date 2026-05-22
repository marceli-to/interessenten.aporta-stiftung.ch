<?php

use App\Models\Media;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    Storage::fake('public');
});

it('lists all media', function () {
    Media::factory()->count(5)->create();

    $this->actingAs($this->user)
        ->getJson('/api/dashboard/media')
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('uploads an image to temp storage', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

    $response = $this->actingAs($this->user)
        ->postJson('/api/dashboard/media/upload', ['file' => $file])
        ->assertOk()
        ->assertJsonStructure(['data' => ['uuid', 'file', 'original_name', 'width', 'height', '_temp']]);

    expect($response->json('data._temp'))->toBeTrue();

    Storage::disk('public')->assertExists('temp/' . $response->json('data.file'));
});

it('rejects non-image uploads', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs($this->user)
        ->postJson('/api/dashboard/media/upload', ['file' => $file])
        ->assertUnprocessable();
});

it('updates media alt and caption', function () {
    $media = Media::factory()->create(['alt' => '', 'caption' => '']);

    $this->actingAs($this->user)
        ->putJson("/api/dashboard/media/{$media->uuid}", [
            'alt' => 'A nice photo',
            'caption' => 'Taken in Zurich',
        ])
        ->assertOk()
        ->assertJsonPath('data.alt', 'A nice photo')
        ->assertJsonPath('data.caption', 'Taken in Zurich');
});

it('deletes media and removes file from storage', function () {
    Storage::disk('public')->put('uploads/test.jpg', 'fake-content');
    $media = Media::factory()->create(['file' => 'test.jpg']);

    $this->actingAs($this->user)
        ->deleteJson("/api/dashboard/media/{$media->uuid}")
        ->assertNoContent();

    expect(Media::count())->toBe(0);
    Storage::disk('public')->assertMissing('uploads/test.jpg');
});

it('reorders media', function () {
    $a = Media::factory()->create(['sort_order' => 0]);
    $b = Media::factory()->create(['sort_order' => 1]);

    $this->actingAs($this->user)
        ->patchJson('/api/dashboard/media/reorder', [
            'items' => [
                ['uuid' => $a->uuid, 'sort_order' => 1],
                ['uuid' => $b->uuid, 'sort_order' => 0],
            ],
        ])
        ->assertOk();

    expect($a->fresh()->sort_order)->toBe(1)
        ->and($b->fresh()->sort_order)->toBe(0);
});

it('rejects reorder with unknown uuid', function () {
    $this->actingAs($this->user)
        ->patchJson('/api/dashboard/media/reorder', [
            'items' => [['uuid' => 'bad-uuid', 'sort_order' => 0]],
        ])
        ->assertUnprocessable();
});

it('toggles teaser on', function () {
    $project = Project::factory()->create();
    $media = Media::factory()->create([
        'mediable_type' => Project::class,
        'mediable_id' => $project->id,
        'is_teaser' => false,
    ]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/media/{$media->uuid}/teaser")
        ->assertOk()
        ->assertJsonPath('data.is_teaser', true);
});

it('toggles teaser off when already set', function () {
    $project = Project::factory()->create();
    $media = Media::factory()->create([
        'mediable_type' => Project::class,
        'mediable_id' => $project->id,
        'is_teaser' => true,
    ]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/media/{$media->uuid}/teaser")
        ->assertOk()
        ->assertJsonPath('data.is_teaser', false);
});

it('only allows one teaser per entity', function () {
    $project = Project::factory()->create();
    $first = Media::factory()->create([
        'mediable_type' => Project::class,
        'mediable_id' => $project->id,
        'is_teaser' => true,
    ]);
    $second = Media::factory()->create([
        'mediable_type' => Project::class,
        'mediable_id' => $project->id,
        'is_teaser' => false,
    ]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/media/{$second->uuid}/teaser")
        ->assertOk()
        ->assertJsonPath('data.is_teaser', true);

    expect($first->fresh()->is_teaser)->toBeFalse();
});

it('toggles og image on', function () {
    $project = Project::factory()->create();
    $media = Media::factory()->create([
        'mediable_type' => Project::class,
        'mediable_id' => $project->id,
        'is_og' => false,
    ]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/media/{$media->uuid}/og")
        ->assertOk()
        ->assertJsonPath('data.is_og', true);
});

it('only allows one og image per entity', function () {
    $project = Project::factory()->create();
    $first = Media::factory()->create([
        'mediable_type' => Project::class,
        'mediable_id' => $project->id,
        'is_og' => true,
    ]);
    $second = Media::factory()->create([
        'mediable_type' => Project::class,
        'mediable_id' => $project->id,
        'is_og' => false,
    ]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/media/{$second->uuid}/og")
        ->assertOk();

    expect($first->fresh()->is_og)->toBeFalse();
});

it('includes crop in media resource', function () {
    $media = Media::factory()->create([
        'crop' => ['x' => 100, 'y' => 50, 'w' => 800, 'h' => 600],
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/dashboard/media')
        ->assertOk()
        ->assertJsonPath('data.0.crop.x', 100)
        ->assertJsonPath('data.0.crop.y', 50)
        ->assertJsonPath('data.0.crop.w', 800)
        ->assertJsonPath('data.0.crop.h', 600);
});

it('appends crop param to thumbnail_url when crop is set', function () {
    $media = Media::factory()->create([
        'file' => 'test-image.jpg',
        'crop' => ['x' => 100, 'y' => 50, 'w' => 800, 'h' => 600],
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/dashboard/media')
        ->assertOk()
        ->assertJsonPath('data.0.thumbnail_url', '/img/uploads/test-image.jpg?w=400&h=400&fit=crop&crop=800,600,100,50');
});

it('does not append crop param when crop is null', function () {
    $media = Media::factory()->create([
        'file' => 'test-image.jpg',
        'crop' => null,
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/dashboard/media')
        ->assertOk()
        ->assertJsonPath('data.0.thumbnail_url', '/img/uploads/test-image.jpg?w=400&h=400&fit=crop');
});

it('sets crop on media', function () {
    $media = Media::factory()->create(['crop' => null]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/media/{$media->uuid}/crop", [
            'x' => 100, 'y' => 50, 'w' => 800, 'h' => 600,
        ])
        ->assertOk()
        ->assertJsonPath('data.crop.x', 100)
        ->assertJsonPath('data.crop.y', 50)
        ->assertJsonPath('data.crop.w', 800)
        ->assertJsonPath('data.crop.h', 600);

    expect($media->fresh()->crop)->toBe(['x' => 100, 'y' => 50, 'w' => 800, 'h' => 600]);
});

it('clears crop on media when null values sent', function () {
    $media = Media::factory()->create([
        'crop' => ['x' => 100, 'y' => 50, 'w' => 800, 'h' => 600],
    ]);

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/media/{$media->uuid}/crop", [
            'x' => null, 'y' => null, 'w' => null, 'h' => null,
        ])
        ->assertOk()
        ->assertJsonPath('data.crop', null);

    expect($media->fresh()->crop)->toBeNull();
});

it('rejects invalid crop values', function () {
    $media = Media::factory()->create();

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/media/{$media->uuid}/crop", [
            'x' => 'bad', 'y' => 50, 'w' => 800, 'h' => 600,
        ])
        ->assertUnprocessable();
});

it('rejects partial crop values', function () {
    $media = Media::factory()->create();

    $this->actingAs($this->user)
        ->patchJson("/api/dashboard/media/{$media->uuid}/crop", [
            'x' => 100, 'y' => null, 'w' => null, 'h' => null,
        ])
        ->assertUnprocessable();
});

it('requires authentication for media', function () {
    $this->getJson('/api/dashboard/media')->assertUnauthorized();
});
