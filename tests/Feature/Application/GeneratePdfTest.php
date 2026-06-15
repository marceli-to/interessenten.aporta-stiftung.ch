<?php

use App\Actions\Application\Pdf\Generate;
use App\Actions\Application\ResolveIds;
use App\Actions\Application\Show;
use App\Enums\ExportStatus;
use App\Enums\Status;
use App\Jobs\GeneratePdf;
use App\Models\Application;
use App\Models\ApplicationExport;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

beforeEach(function () {
	Carbon::setTestNow('2026-05-22T10:30:00Z');
	$this->user = User::factory()->create();
});

afterEach(function () {
	Carbon::setTestNow();
});

/**
 * A renderer that records its call instead of producing a PDF, so the job's
 * orchestration can be tested without Chrome or the Blade view.
 */
function recordingRenderer(): Generate
{
	return new class extends Generate {
		public ?array $captured = null;

		public function execute(Collection $applications, string $disk, string $path): void
		{
			$this->captured = [
				'ids' => $applications->pluck('id')->all(),
				'disk' => $disk,
				'path' => $path,
			];
		}
	};
}

function runExportJob(GeneratePdf $job, Generate $renderer): void
{
	$job->handle(app(ResolveIds::class), app(Show::class), $renderer);
}

it('renders the selected applications and marks the export ready', function () {
	$apps = Application::factory()->withFullAggregate()->count(3)->create();
	$export = ApplicationExport::factory()->create(['user_id' => $this->user->id]);

	$renderer = recordingRenderer();
	$job = new GeneratePdf($export, ids: $apps->pluck('id')->all());

	runExportJob($job, $renderer);

	// Renderer got exactly the selected applications, stored under the export id.
	expect($renderer->captured)->not->toBeNull();
	expect($renderer->captured['ids'])->toEqualCanonicalizing($apps->pluck('id')->all());
	expect($renderer->captured['disk'])->toBe('local');
	expect($renderer->captured['path'])->toBe("exports/{$export->id}/applications.pdf");

	$export->refresh();
	expect($export->status)->toBe(ExportStatus::Ready);
	expect($export->disk)->toBe('local');
	expect($export->path)->toBe("exports/{$export->id}/applications.pdf");
	expect($export->application_count)->toBe(3);
	expect($export->expires_at->toIso8601String())->toBe(now()->addHours(24)->toIso8601String());
});

it('resolves an all-matching selection from the filter', function () {
	$opened = Application::factory()->withFullAggregate()->count(2)->create(['status' => Status::Opened]);
	Application::factory()->withFullAggregate()->create(['status' => Status::Archived]);

	$export = ApplicationExport::factory()->create(['user_id' => $this->user->id]);
	$renderer = recordingRenderer();
	$job = new GeneratePdf($export, filters: ['status' => [Status::Opened->value]]);

	runExportJob($job, $renderer);

	expect($renderer->captured['ids'])->toEqualCanonicalizing($opened->pluck('id')->all());
	expect($export->refresh()->application_count)->toBe(2);
});

it('drops excluded ids in all-matching mode', function () {
	$apps = Application::factory()->withFullAggregate()->count(3)->create(['status' => Status::Opened]);
	$drop = $apps->first()->id;

	$export = ApplicationExport::factory()->create(['user_id' => $this->user->id]);
	$renderer = recordingRenderer();
	$job = new GeneratePdf(
		$export,
		filters: ['status' => [Status::Opened->value]],
		exclude: [$drop],
	);

	runExportJob($job, $renderer);

	expect($renderer->captured['ids'])->not->toContain($drop);
	expect($export->refresh()->application_count)->toBe(2);
});

it('includes soft-deleted rows for an explicit selection', function () {
	$apps = Application::factory()->withFullAggregate()->count(2)->create();
	$apps->each->delete();

	$export = ApplicationExport::factory()->create(['user_id' => $this->user->id]);
	$renderer = recordingRenderer();
	$job = new GeneratePdf($export, ids: $apps->pluck('id')->all());

	runExportJob($job, $renderer);

	expect($renderer->captured['ids'])->toEqualCanonicalizing($apps->pluck('id')->all());
	expect($export->refresh()->status)->toBe(ExportStatus::Ready);
});

it('fails without rendering when the selection resolves to nothing', function () {
	$export = ApplicationExport::factory()->create(['user_id' => $this->user->id]);
	$renderer = recordingRenderer();
	// All-matching on a status no row has → empty selection.
	$job = new GeneratePdf($export, filters: ['status' => [Status::Archived->value]]);

	runExportJob($job, $renderer);

	expect($renderer->captured)->toBeNull();
	$export->refresh();
	expect($export->status)->toBe(ExportStatus::Failed);
	expect($export->failure_reason)->toBe('Die Auswahl enthält keine Bewerbungen.');
	expect($export->path)->toBeNull();
});

it('propagates a render failure so the queue can retry, leaving the row pending', function () {
	$apps = Application::factory()->withFullAggregate()->count(1)->create();
	$export = ApplicationExport::factory()->create(['user_id' => $this->user->id]);

	$renderer = new class extends Generate {
		public function execute(Collection $applications, string $disk, string $path): void
		{
			throw new RuntimeException('Chrome exploded');
		}
	};
	$job = new GeneratePdf($export, ids: $apps->pluck('id')->all());

	expect(fn () => runExportJob($job, $renderer))->toThrow(RuntimeException::class);

	// Stays pending while retries remain; failed() handles the terminal state.
	expect($export->refresh()->status)->toBe(ExportStatus::Pending);
});

it('marks the export failed once retries are exhausted', function () {
	$export = ApplicationExport::factory()->create(['user_id' => $this->user->id]);
	$job = new GeneratePdf($export);

	$job->failed(new RuntimeException('Chrome exploded'));

	$export->refresh();
	expect($export->status)->toBe(ExportStatus::Failed);
	expect($export->failure_reason)->toBe('Chrome exploded');
});
