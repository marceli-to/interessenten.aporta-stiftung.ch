<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\User;
use App\Support\Legacy\LegacyImporter;
use Database\Seeders\AportaUserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Imports the legacy applications from storage/import/*.json into the new schema.
 * Idempotent on reference_number (= form_nr): existing records are skipped unless
 * --fresh wipes everything first. Each application is built inside its own
 * transaction, so a bad record fails alone. See docs/legacy-import.md.
 */
class ImportLegacy extends Command
{
	protected $signature = 'app:import-legacy
		{--dir= : Override the import directory (default storage/import)}
		{--fresh : Force-delete ALL existing applications first, then import}
		{--limit=0 : Import only the first N files (0 = all)}';

	protected $description = 'Import legacy applications from storage/import/*.json';

	public function handle(): int
	{
		$dir = $this->option('dir') ?: storage_path('import');
		$files = glob(rtrim($dir, '/') . '/*.json') ?: [];
		sort($files, SORT_NATURAL);

		if ($limit = (int) $this->option('limit')) {
			$files = array_slice($files, 0, $limit);
		}

		if (! $files) {
			$this->error("No JSON files found in {$dir}");

			return self::FAILURE;
		}

		if ($this->option('fresh')) {
			if (! $this->confirm('This force-deletes ALL existing applications (and their applicants, notes, etc.). Continue?', false)) {
				return self::FAILURE;
			}
			$deleted = Application::withTrashed()->forceDelete();
			$this->warn("Wiped {$deleted} existing applications.");
		}

		$author = $this->resolveAuthor();
		$importer = new LegacyImporter($author->id);

		$existing = Application::withTrashed()->pluck('id', 'reference_number')->all();

		$imported = 0;
		$skipped = 0;
		$failed = [];

		$this->info('Importing ' . count($files) . " files as author {$author->full_name} …");
		$bar = $this->output->createProgressBar(count($files));

		foreach ($files as $file) {
			$bar->advance();
			$nr = (int) basename($file, '.json');
			$data = json_decode((string) file_get_contents($file), true);

			if (! is_array($data) || ! isset($data['form_nr'])) {
				$failed[$nr] = 'unreadable JSON';

				continue;
			}

			if (isset($existing[$data['form_nr']])) {
				$skipped++;

				continue;
			}

			try {
				DB::transaction(fn () => $importer->import($data));
				$imported++;
			} catch (\Throwable $e) {
				$failed[$data['form_nr']] = $e->getMessage();
			}
		}

		$bar->finish();
		$this->newLine(2);

		$this->advanceReferenceSequence($files);

		$this->info("Imported: {$imported}   Skipped (already present): {$skipped}   Failed: " . count($failed));
		if ($failed) {
			$this->newLine();
			$this->warn('Failures:');
			foreach (array_slice($failed, 0, 25, true) as $ref => $msg) {
				$this->line("  #{$ref}: {$msg}");
			}
			if (count($failed) > 25) {
				$this->line('  … ' . (count($failed) - 25) . ' more');
			}

			return self::FAILURE;
		}

		return self::SUCCESS;
	}

	/** Laura Cerny owns every imported note (docs/legacy-import.md). Ensure she exists. */
	private function resolveAuthor(): User
	{
		$author = User::where('email', AportaUserSeeder::NOTE_AUTHOR_EMAIL)->first();

		if (! $author) {
			(new AportaUserSeeder())->run();
			$author = User::where('email', AportaUserSeeder::NOTE_AUTHOR_EMAIL)->firstOrFail();
		}

		return $author;
	}

	/**
	 * Imported reference numbers reuse the legacy form_nr (max 1876), but the intake
	 * sequence starts at 1 — so advance it past every legacy number to avoid future
	 * collisions. Driven off the full file set so partial runs still bump correctly.
	 */
	private function advanceReferenceSequence(array $files): void
	{
		$maxFormNr = 0;
		foreach ($files as $file) {
			$maxFormNr = max($maxFormNr, (int) basename($file, '.json'));
		}

		if ($maxFormNr === 0) {
			return;
		}

		if (DB::connection()->getDriverName() === 'mysql') {
			DB::statement('ALTER TABLE application_reference_seq AUTO_INCREMENT = ' . ($maxFormNr + 1));
		} else {
			DB::table('application_reference_seq')->delete();
			DB::table('application_reference_seq')->insert(['id' => $maxFormNr]);
		}

		$this->line("Reference-number sequence advanced to " . ($maxFormNr + 1) . '.');
	}
}
