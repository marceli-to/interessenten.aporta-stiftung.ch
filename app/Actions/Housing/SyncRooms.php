<?php

namespace App\Actions\Housing;

use App\Enums\Room;
use App\Models\Application;
use Illuminate\Support\Facades\DB;

class SyncRooms
{
	/**
	 * Materialize the derived room range for an application. Rooms are not a free
	 * choice: the eligible whole-room sizes follow the household size (persons ± 1,
	 * clamped to 2..6 — see Room::rangeForPersons()). Re-run on every write that can
	 * change total_persons so the pivot never drifts from the person count.
	 */
	public function execute(Application $application): void
	{
		$rows = array_map(fn (string $slug) => [
			'application_id' => $application->id,
			'room_slug' => $slug,
		], Room::slugsForPersons((int) $application->total_persons));

		DB::table('application_rooms')->where('application_id', $application->id)->delete();
		DB::table('application_rooms')->insert($rows);
	}
}
