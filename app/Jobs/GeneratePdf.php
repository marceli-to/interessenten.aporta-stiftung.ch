<?php

namespace App\Jobs;

use App\Actions\Application\Pdf\Generate;
use App\Actions\Application\ResolveIds;
use App\Actions\Application\Show;
use App\Enums\ExportStatus;
use App\Models\ApplicationExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Generate a single PDF for a selection of applications, asynchronously.
 *
 * The export is started by an endpoint that creates the {@see ApplicationExport}
 * tracking row (status `pending`) and dispatches this job, then hands the client
 * an id to poll. An all-matching selection can resolve to hundreds of rows, so
 * the work lives here rather than in the request to avoid Lambda/request timeouts.
 *
 * Selection is carried verbatim (same `{ ids }` or `{ filters, exclude }` shape
 * as the bulk actions) and resolved at run time through the shared list query, so
 * the export acts on exactly the rows the list would show.
 */
class GeneratePdf implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public int $tries = 3;

	/** @var array<int, int> */
	public array $backoff = [10, 30, 60];

	/**
	 * @param  array<int, int>|null  $ids
	 * @param  array<string, mixed>  $filters
	 * @param  array<int, int>  $exclude
	 */
	public function __construct(
		public ApplicationExport $export,
		public ?array $ids = null,
		public ?string $search = null,
		public string $sort = 'opened_at',
		public string $direction = 'desc',
		public array $filters = [],
		public array $exclude = [],
	) {}

	public function handle(ResolveIds $resolveIds, Show $show, Generate $renderer): void
	{
		$ids = $resolveIds->execute(
			ids: $this->ids,
			search: $this->search,
			sort: $this->sort,
			direction: $this->direction,
			filters: $this->filters,
			exclude: $this->exclude,
		);

		// An all-matching selection can legitimately resolve to nothing (filter
		// matches no rows). That is a permanent outcome, not a transient render
		// failure, so mark it failed without burning retries.
		if ($ids === []) {
			$this->export->update([
				'status' => ExportStatus::Failed,
				'failure_reason' => 'Die Auswahl enthält keine Bewerbungen.',
			]);

			return;
		}

		$applications = $show->loadMany($ids);

		$disk = config('aporta.exports.disk');
		$path = "exports/{$this->export->id}/applications.pdf";

		$renderer->execute($applications, $disk, $path);

		$this->export->update([
			'status' => ExportStatus::Ready,
			'disk' => $disk,
			'path' => $path,
			'application_count' => $applications->count(),
			'expires_at' => now()->addHours((int) config('aporta.exports.ttl_hours', 24)),
		]);
	}

	/**
	 * Runs after the retries are exhausted (or on a non-retryable throw), so the
	 * row stays `pending` while attempts remain and the poller keeps waiting.
	 */
	public function failed(?Throwable $exception): void
	{
		$this->export->update([
			'status' => ExportStatus::Failed,
			'failure_reason' => $exception?->getMessage(),
		]);
	}
}
