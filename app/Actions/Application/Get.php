<?php

namespace App\Actions\Application;

use App\Models\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class Get
{
	/**
	 * Paginated list of applications for the dashboard, each enriched with its
	 * room/district slug lists. The pivots are fetched in two batched queries
	 * (keyed by application id) rather than one query per row.
	 */
	public function execute(int $perPage = 25): LengthAwarePaginator
	{
		$applications = Application::query()
			->with(['mainApplicant.employer'])
			->orderByDesc('opened_at')
			->paginate($perPage);

		$ids = $applications->pluck('id');

		$rooms = DB::table('application_rooms')
			->whereIn('application_id', $ids)
			->get()
			->groupBy('application_id');

		$districts = DB::table('application_districts')
			->whereIn('application_id', $ids)
			->get()
			->groupBy('application_id');

		$applications->each(function (Application $application) use ($rooms, $districts) {
			$application->room_slugs = $rooms->get($application->id, collect())->pluck('room_slug')->all();
			$application->district_slugs = $districts->get($application->id, collect())->pluck('district_slug')->all();
		});

		return $applications;
	}
}
