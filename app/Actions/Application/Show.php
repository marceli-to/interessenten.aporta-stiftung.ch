<?php

namespace App\Actions\Application;

use App\Models\Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class Show
{
	/**
	 * Preference pivots that live outside the relation graph (plain pivot tables,
	 * mirroring Application\Get): [attribute => [table, slug column]].
	 */
	private const SLUG_PIVOTS = [
		'room_slugs' => ['application_rooms', 'room_slug'],
		'district_slugs' => ['application_districts', 'district_slug'],
		'floor_slugs' => ['application_floors', 'floor_slug'],
	];

	/**
	 * Every relation the detail view (and the PDF export) needs, in one place so
	 * the single-row load and the bulk export load can never drift apart.
	 *
	 * @return array<int|string, mixed>
	 */
	public static function relations(): array
	{
		return [
			'mainApplicant.employer',
			'mainApplicant.currentHousing',
			'coApplicants.employer',
			'coApplicants.currentHousing',
			'children' => fn ($query) => $query->orderBy('position'),
			'notes' => fn ($query) => $query->with('user')->latest(),
			'statusEvents' => fn ($query) => $query->with('actor')->orderByDesc('occurred_at'),
		];
	}

	/**
	 * Load a single application with every relation the detail view edits, plus
	 * its room/district/floor preference slugs.
	 */
	public function execute(Application $application): Application
	{
		$application->load(self::relations());

		$this->attachPreferenceSlugs(new Collection([$application]));

		return $application;
	}

	/**
	 * Load many applications with the full detail dataset, ordered to match the
	 * given id list (which the caller has already put in list order). Used by the
	 * PDF export, which renders the same data shape as the detail view for a
	 * selection that may include soft-deleted rows.
	 *
	 * @param  array<int, int>  $orderedIds
	 * @return Collection<int, Application>
	 */
	public function loadMany(array $orderedIds): Collection
	{
		$applications = Application::withTrashed()
			->whereIn('id', $orderedIds)
			->with(self::relations())
			->get()
			->sortBy(fn (Application $application) => array_search($application->id, $orderedIds, true))
			->values();

		$this->attachPreferenceSlugs($applications);

		return $applications;
	}

	/**
	 * Attach the preference slug arrays to a collection of applications in one
	 * grouped query per pivot, so a bulk load stays at three extra queries
	 * regardless of how many rows are being loaded.
	 *
	 * @param  Collection<int, Application>  $applications
	 */
	private function attachPreferenceSlugs(Collection $applications): void
	{
		if ($applications->isEmpty()) {
			return;
		}

		$ids = $applications->pluck('id')->all();

		foreach (self::SLUG_PIVOTS as $attribute => [$table, $column]) {
			$byApplication = DB::table($table)
				->whereIn('application_id', $ids)
				->get([$column, 'application_id'])
				->groupBy('application_id');

			foreach ($applications as $application) {
				$application->{$attribute} = ($byApplication[$application->id] ?? collect())
					->pluck($column)
					->all();
			}
		}
	}
}
