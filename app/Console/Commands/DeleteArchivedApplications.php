<?php

namespace App\Console\Commands;

use App\Actions\Application\DeleteArchived;
use Illuminate\Console\Command;

class DeleteArchivedApplications extends Command
{
	protected $signature = 'app:delete-archived';

	protected $description = 'Soft-delete archived applications past the 3 month retention window.';

	public function handle(DeleteArchived $deleteArchived): int
	{
		$count = $deleteArchived->execute();

		$this->info("Deleted {$count} archived application(s).");

		return self::SUCCESS;
	}
}
