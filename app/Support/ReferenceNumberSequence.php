<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Allocates the next `applications.reference_number` from a dedicated
 * AUTO_INCREMENT-backed sequence table (`application_reference_seq`).
 *
 * The table is created in the applications migration with its start value
 * seeded from `config('aporta.reference_number_start')`.
 */
class ReferenceNumberSequence
{
	public function next(): int
	{
		return (int) DB::transaction(function () {
			$id = DB::table('application_reference_seq')->insertGetId([]);
			DB::table('application_reference_seq')->where('id', '<', $id)->delete();

			return $id;
		});
	}
}
