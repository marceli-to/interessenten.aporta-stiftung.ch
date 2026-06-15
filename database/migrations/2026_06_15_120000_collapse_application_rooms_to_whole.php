<?php

use App\Enums\Room;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rooms are no longer a free multi-select. The eligible room sizes are now derived
 * from the household size: rooms = persons ± 1, in whole rooms only, clamped to
 * 2..6 (see Room::rangeForPersons()). The half-room steps were dropped.
 *
 * This rewrites every application's room pivot to that derived range, discarding
 * the original free selection (84% were ranges, many with half-room steps). Driven
 * off total_persons, which is untouched — the original choice cannot be restored,
 * so down() is a no-op.
 */
return new class extends Migration
{
	public function up(): void
	{
		// Includes soft-deleted applications: their pivots are migrated too so the
		// dataset is uniform no matter the row's lifecycle state.
		foreach (DB::table('applications')->select('id', 'total_persons')->cursor() as $application) {
			$rows = array_map(fn (string $slug) => [
				'application_id' => $application->id,
				'room_slug' => $slug,
			], Room::slugsForPersons((int) $application->total_persons));

			DB::table('application_rooms')->where('application_id', $application->id)->delete();
			DB::table('application_rooms')->insert($rows);
		}
	}

	public function down(): void
	{
		// Irreversible: the original room selections are gone. Nothing to restore.
	}
};
