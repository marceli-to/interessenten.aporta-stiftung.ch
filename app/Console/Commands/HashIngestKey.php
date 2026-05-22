<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class HashIngestKey extends Command
{
	protected $signature = 'aporta:hash-key {--generate : Generate a fresh random 64-char key and print both raw + hash}';

	protected $description = 'Hash an ingest API key with SHA-256, ready to paste into APORTA_INGEST_API_KEY_HASH.';

	public function handle(): int
	{
		if ($this->option('generate')) {
			$raw = Str::random(64);
			$this->line('Raw key (give to Statamic side):');
			$this->line($raw);
			$this->newLine();
			$this->line('Hash (set as APORTA_INGEST_API_KEY_HASH in .env):');
			$this->line(hash('sha256', $raw));

			return self::SUCCESS;
		}

		$raw = $this->secret('Paste the raw ingest API key');
		if (! $raw) {
			$this->error('No key provided.');

			return self::FAILURE;
		}

		$this->line(hash('sha256', $raw));

		return self::SUCCESS;
	}
}
