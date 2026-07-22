<?php

namespace App\Actions\Application;

use App\Actions\Applicant\Upsert as UpsertApplicant;
use App\Actions\Children\Sync as SyncChildren;
use App\Actions\Housing\Sync as SyncHousing;
use App\Actions\Housing\SyncRooms;
use App\Models\Applicant;
use App\Models\Application;
use Illuminate\Support\Facades\DB;

class Update
{
	public function __construct(
		private UpsertApplicant $upsertApplicant,
		private SyncHousing $syncHousing,
		private SyncRooms $syncRooms,
		private SyncChildren $syncChildren,
	) {}

	/**
	 * Apply a partial update to an Application. Each top-level section
	 * (housing_wish, household_info, main_applicant, co_applicant, children)
	 * is treated as a full replacement for that section when present, and
	 * left untouched when absent. Intake-only fields (submission_id, submitted_*,
	 * opened_at, reference_number) and status are ignored — status transitions
	 * go through RecordStatusEventAction on a dedicated endpoint.
	 */
	public function execute(Application $application, array $data): Application
	{
		return DB::transaction(function () use ($application, $data) {
			$application->fill($this->editableApplicationAttributes($data));

			if (array_key_exists('housing_wish', $data) && is_array($data['housing_wish'])) {
				$application->fill($this->housingAttributes($data['housing_wish']));
				$this->syncHousing->execute($application, $data['housing_wish']);
			}

			if (array_key_exists('household_info', $data) && is_array($data['household_info'])) {
				$application->fill($this->householdAttributes($data['household_info']));
			}

			// Adding or removing the partner moves the household by one adult.
			// Must be read before the co_applicant section below is applied.
			$adultDelta = $this->coApplicantAdultDelta($application, $data);

			if ($adultDelta !== 0) {
				$application->adults_count = max(1, $application->adults_count + $adultDelta);
				$application->total_persons = $application->adults_count + $application->children_count;
			}

			$application->last_changed_at = now();
			$application->save();

			// The room range is derived from the household size. Recompute it
			// whenever household_info is touched so it never drifts from persons.
			if ((array_key_exists('household_info', $data) && is_array($data['household_info'])) || $adultDelta !== 0) {
				$this->syncRooms->execute($application);
			}

			if (array_key_exists('main_applicant', $data) && is_array($data['main_applicant'])) {
				$this->upsertApplicant->execute($application, $data['main_applicant'], 'main_applicant', 1);
			}

			if (array_key_exists('co_applicant', $data)) {
				if ($data['co_applicant'] === null) {
					Applicant::where('application_id', $application->id)
						->where('role', 'co_applicant')
						->delete();
				} elseif (is_array($data['co_applicant'])) {
					$this->upsertApplicant->execute($application, $data['co_applicant'], 'co_applicant', 2);
				}
			}

			if (array_key_exists('children', $data) && is_array($data['children'])) {
				$this->syncChildren->execute($application, $data['children']);
			}

			return $application->fresh();
		});
	}

	/**
	 * How many adults the co_applicant section adds to (1) or removes from (-1)
	 * the household — a partner is an adult, so "Personen im Haushalt" must move
	 * with them instead of waiting for someone to correct it by hand. Only the
	 * transitions count: editing an existing partner changes nothing. An explicit
	 * household_info in the same payload wins — the numbers were just set there.
	 */
	private function coApplicantAdultDelta(Application $application, array $data): int
	{
		if (! array_key_exists('co_applicant', $data) || array_key_exists('household_info', $data)) {
			return 0;
		}

		$had = Applicant::where('application_id', $application->id)
			->where('role', 'co_applicant')
			->exists();
		$has = $data['co_applicant'] !== null;

		return match (true) {
			! $had && $has => 1,
			$had && ! $has => -1,
			default => 0,
		};
	}

	/**
	 * @return array<string, mixed>
	 */
	private function editableApplicationAttributes(array $data): array
	{
		return array_intersect_key($data, array_flip([
			'shares_apartment',
		]));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function housingAttributes(array $housing): array
	{
		return array_intersect_key($housing, array_flip([
			'wants_elevator',
			'max_gross_rent',
			'earliest_move_in',
		]));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function householdAttributes(array $household): array
	{
		return array_intersect_key($household, array_flip([
			'total_persons',
			'adults_count',
			'children_count',
			'all_children_live_constantly',
			'has_pets',
			'pets_description',
			'remarks',
		]));
	}
}
