<?php

namespace App\Actions\Application;

use App\Models\Application;
use Illuminate\Support\Facades\DB;

class Show
{
	/**
	 * Load a single application with every relation the detail view edits, plus
	 * its room/district/floor preference slugs (kept out of the relation graph
	 * because they live in plain pivot tables, mirroring Application\Get).
	 */
	public function execute(Application $application): Application
	{
		$application->load([
			'mainApplicant.employer',
			'mainApplicant.currentHousing',
			'coApplicants.employer',
			'coApplicants.currentHousing',
			'children' => fn ($query) => $query->orderBy('position'),
		]);

		$application->room_slugs = $this->slugs('application_rooms', 'room_slug', $application->id);
		$application->district_slugs = $this->slugs('application_districts', 'district_slug', $application->id);
		$application->floor_slugs = $this->slugs('application_floors', 'floor_slug', $application->id);

		return $application;
	}

	/**
	 * @return array<int, string>
	 */
	private function slugs(string $table, string $column, int $applicationId): array
	{
		return DB::table($table)
			->where('application_id', $applicationId)
			->pluck($column)
			->all();
	}
}
