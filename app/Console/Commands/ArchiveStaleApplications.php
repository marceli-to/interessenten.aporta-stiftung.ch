<?php

namespace App\Console\Commands;

use App\Actions\Application\ArchiveStale;
use Illuminate\Console\Command;

class ArchiveStaleApplications extends Command
{
	protected $signature = 'app:archive-stale';

	protected $description = 'Auto-archive open/extended applications past the 6+3 month deadline.';

	public function handle(ArchiveStale $archiveStale): int
	{
		$count = $archiveStale->execute();

		$this->info("Archived {$count} stale application(s).");

		return self::SUCCESS;
	}
}
