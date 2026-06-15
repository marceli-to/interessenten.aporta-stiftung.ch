<?php

namespace App\Console\Commands;

use App\Actions\Application\Pdf\Generate;
use App\Actions\Application\Show;
use App\Enums\Status;
use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportApplicationsPdf extends Command
{
	protected $signature = 'app:export-pdf
		{ids?* : Application IDs to include (omit when using --all)}
		{--all : Export every application, optionally narrowed by --status}
		{--status=* : Status slug(s) to keep when using --all (opened, extended, archived, knif)}
		{--limit=0 : Cap the number of applications (0 = no cap)}
		{--output= : Write the PDF here; defaults to storage/app/exports/cli/…}';

	protected $description = 'Render one or more applications into a real PDF (same template as the export).';

	public function handle(Show $show, Generate $generate): int
	{
		$ids = $this->resolveIds();

		if ($ids === []) {
			$this->error('No applications selected. Pass IDs (app:export-pdf 1 2 3) or use --all.');

			return self::FAILURE;
		}

		$applications = $show->loadMany($ids);

		if ($applications->isEmpty()) {
			$this->error('None of the given IDs matched an application.');

			return self::FAILURE;
		}

		$disk = 'local';
		$path = 'exports/cli/applications-'.now()->format('Ymd-His').'.pdf';

		$this->info("Rendering {$applications->count()} application(s)…");

		try {
			$generate->execute($applications, $disk, $path);
		} catch (Throwable $e) {
			$this->error('PDF rendering failed: '.$e->getMessage());
			$this->line('Browsershot needs Node + a Chrome/Puppeteer install locally; '
				.'in production this offloads to the Sidecar Lambda.');

			return self::FAILURE;
		}

		$full = Storage::disk($disk)->path($path);

		if ($output = $this->option('output')) {
			File::ensureDirectoryExists(dirname($output));
			File::copy($full, $output);
			Storage::disk($disk)->delete($path);
			$full = $output;
		}

		$this->info("PDF created: {$full}");

		return self::SUCCESS;
	}

	/**
	 * @return array<int, int>
	 */
	private function resolveIds(): array
	{
		if ($this->option('all')) {
			$query = Application::query();

			if ($statuses = $this->validatedStatuses()) {
				$query->whereIn('status', $statuses);
			}

			$query->orderByDesc('opened_at')->orderByDesc('id');

			if (($limit = (int) $this->option('limit')) > 0) {
				$query->limit($limit);
			}

			return $query->pluck('id')->all();
		}

		$ids = array_map('intval', $this->argument('ids'));

		if (($limit = (int) $this->option('limit')) > 0) {
			$ids = array_slice($ids, 0, $limit);
		}

		return $ids;
	}

	/**
	 * @return array<int, string>
	 */
	private function validatedStatuses(): array
	{
		return collect($this->option('status'))
			->map(fn (string $slug) => Status::tryFrom($slug)?->value)
			->filter()
			->values()
			->all();
	}
}
