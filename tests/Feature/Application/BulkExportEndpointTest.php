<?php

use App\Enums\Status;
use App\Models\Application;
use App\Models\User;
use Spatie\LaravelPdf\Facades\Pdf;

beforeEach(function () {
	$this->user = User::factory()->create();
});

it('streams a PDF for an explicit selection', function () {
	Pdf::fake();
	$apps = Application::factory()->withFullAggregate()->count(3)->create();

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-export', [
			'ids' => $apps->pluck('id')->all(),
		])
		->assertOk();

	Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->viewName === 'pdf.applications'
		&& count($pdf->viewData['applications']) === 3);
});

it('exports an all-matching selection from the filter', function () {
	Pdf::fake();
	Application::factory()->withFullAggregate()->count(2)->create(['status' => Status::Opened]);
	Application::factory()->withFullAggregate()->create(['status' => Status::Archived]);

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-export', ['status' => Status::Opened->value])
		->assertOk();

	Pdf::assertRespondedWithPdf(fn ($pdf) => count($pdf->viewData['applications']) === 2);
});

it('rejects a selection larger than the sync cap', function () {
	Pdf::fake();
	config()->set('aporta.exports.max_sync', 2);
	Application::factory()->withFullAggregate()->count(3)->create(['status' => Status::Opened]);

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-export', ['status' => Status::Opened->value])
		->assertStatus(422)
		->assertJsonPath('message', 'Die Auswahl umfasst 3 Bewerbungen. Bitte den Filter auf höchstens 2 eingrenzen.');

});

it('rejects an all-matching selection that resolves to nothing', function () {
	Pdf::fake();
	Application::factory()->withFullAggregate()->create(['status' => Status::Opened]);

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-export', ['status' => Status::Archived->value])
		->assertStatus(422)
		->assertJsonPath('message', 'Die Auswahl enthält keine Bewerbungen.');

});

it('rejects an unscoped request (neither ids nor filter)', function () {
	Application::factory()->withFullAggregate()->create();

	$this->actingAs($this->user)
		->postJson('/api/dashboard/applications/bulk-export', [])
		->assertStatus(422)
		->assertJsonValidationErrors('ids');
});

it('requires authentication', function () {
	$apps = Application::factory()->withFullAggregate()->create();

	$this->postJson('/api/dashboard/applications/bulk-export', [
		'ids' => [$apps->id],
	])->assertUnauthorized();
});
