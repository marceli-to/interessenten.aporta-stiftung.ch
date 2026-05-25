<?php

namespace App\Actions\Housing;

use App\Models\Application;
use Illuminate\Support\Facades\DB;

class Sync
{
	public function execute(Application $application, array $housing): void
	{
		$this->syncPivot('application_districts', 'district_slug', $application->id, $housing['districts'] ?? []);
		$this->syncPivot('application_floors', 'floor_slug', $application->id, $housing['floors'] ?? []);
		$this->syncPivot('application_rooms', 'room_slug', $application->id, $housing['rooms'] ?? []);
	}

	private function syncPivot(string $table, string $slugColumn, int $applicationId, array $slugs): void
	{
		DB::table($table)->where('application_id', $applicationId)->delete();

		foreach (array_unique($slugs) as $slug) {
			DB::table($table)->insert([
				'application_id' => $applicationId,
				$slugColumn => $slug,
			]);
		}
	}
}
