<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Floors are no longer a per-Stock multi-select. The choice collapses to two
 * options: EG/Hochparterre (eg_hochparterre) and Obergeschoss (obergeschoss).
 *
 * This rewrites every application's floor pivot:
 *   hochparterre      → eg_hochparterre
 *   floor_1 … floor_6 → obergeschoss
 *
 * An application that picked several upper floors collapses to a single
 * obergeschoss row (the pivot's composite PK forbids duplicates). The original
 * per-floor choice cannot be restored, so down() is a no-op.
 */
return new class extends Migration
{
	private const MAP = [
		'hochparterre' => 'eg_hochparterre',
		'floor_1' => 'obergeschoss',
		'floor_2' => 'obergeschoss',
		'floor_3' => 'obergeschoss',
		'floor_4' => 'obergeschoss',
		'floor_5' => 'obergeschoss',
		'floor_6' => 'obergeschoss',
	];

	public function up(): void
	{
		// Grouped by application, including soft-deleted ones: their pivots are
		// migrated too so the dataset is uniform regardless of lifecycle state.
		$byApplication = DB::table('application_floors')
			->orderBy('application_id')
			->get()
			->groupBy('application_id');

		foreach ($byApplication as $applicationId => $pivotRows) {
			$slugs = array_values(array_unique(array_map(
				fn ($row) => self::MAP[$row->floor_slug] ?? $row->floor_slug,
				$pivotRows->all(),
			)));

			$rows = array_map(fn (string $slug) => [
				'application_id' => $applicationId,
				'floor_slug' => $slug,
			], $slugs);

			DB::table('application_floors')->where('application_id', $applicationId)->delete();
			DB::table('application_floors')->insert($rows);
		}
	}

	public function down(): void
	{
		// Irreversible: the original per-floor selections are gone. Nothing to restore.
	}
};
