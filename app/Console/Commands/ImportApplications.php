<?php

namespace App\Console\Commands;

use App\Actions\Application\Store as StoreApplication;
use App\Jobs\NotifyNewApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class ImportApplications extends Command
{
	protected $signature = 'applications:import
		{path=storage/seed/applications : Directory containing exported *.json application payloads}';

	protected $description = 'Import exported application payloads (JSON) into the database.';

	public function handle(): int
	{
		$store = new StoreApplication();

		$path = $this->argument('path');
		if (! str_starts_with($path, '/')) {
			$path = base_path($path);
		}

		if (! is_dir($path)) {
			$this->error("Not a directory: {$path}");

			return self::FAILURE;
		}

		$files = glob(rtrim($path, '/').'/*.json');
		if (empty($files)) {
			$this->error("No *.json files found in {$path}");

			return self::FAILURE;
		}

		// Decode upfront so we can import in chronological submission order,
		// keeping reference numbers aligned with the original timeline.
		$payloads = [];
		foreach ($files as $file) {
			$data = json_decode(file_get_contents($file), true);
			if (! is_array($data)) {
				$this->warn('Skipping unreadable JSON: '.basename($file));

				continue;
			}
			$payloads[] = [$file, $data];
		}

		usort($payloads, fn ($a, $b) => strcmp(
			$a[1]['submitted_meta']['submitted_at'] ?? '',
			$b[1]['submitted_meta']['submitted_at'] ?? '',
		));

		// Historical import: don't fire the new-application notification email.
		Queue::fake([NotifyNewApplication::class]);

		$created = 0;
		$skipped = 0;
		$failed = [];

		$bar = $this->output->createProgressBar(count($payloads));
		$bar->start();

		foreach ($payloads as [$file, $data]) {
			try {
				$application = $store->execute($data);
				$application->wasRecentlyCreated ? $created++ : $skipped++;
			} catch (\Throwable $e) {
				$failed[] = basename($file).': '.$e->getMessage();
			}
			$bar->advance();
		}

		$bar->finish();
		$this->newLine(2);

		$this->info("Imported: {$created} created, {$skipped} already existed.");

		if (! empty($failed)) {
			$this->newLine();
			$this->error(count($failed).' failed:');
			foreach ($failed as $line) {
				$this->line('  - '.$line);
			}

			return self::FAILURE;
		}

		return self::SUCCESS;
	}
}
