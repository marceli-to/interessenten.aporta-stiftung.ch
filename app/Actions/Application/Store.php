<?php

namespace App\Actions\Application;

use App\Actions\Applicant\Upsert as UpsertApplicant;
use App\Actions\Children\Sync as SyncChildren;
use App\Actions\Housing\Sync as SyncHousing;
use App\Actions\Status\Record as RecordStatus;
use App\Enums\Status;
use App\Jobs\NotifyNewApplication;
use App\Models\Application;
use App\Support\ReferenceNumberSequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class Store
{
	public function execute(array $data, ?ReferenceNumberSequence $sequence = null): Application
	{
		$sequence ??= app(ReferenceNumberSequence::class);

		$existing = Application::where('submission_id', $data['submission_id'])->first();
		if ($existing) {
			return $existing;
		}

		try {
			$application = DB::transaction(function () use ($data, $sequence) {
				return $this->createAggregate($data, $sequence);
			});
		} catch (QueryException $e) {
			// Race: another worker inserted the same submission_id between our SELECT and INSERT.
			// Re-fetch and return the winner.
			$existing = Application::where('submission_id', $data['submission_id'])->first();
			if ($existing) {
				return $existing;
			}
			throw $e;
		}

		NotifyNewApplication::dispatch($application->id);

		return $application;
	}

	private function createAggregate(array $data, ReferenceNumberSequence $sequence): Application
	{
		$housing = $data['housing_wish'];
		$household = $data['household_info'];
		$submittedAt = $data['submitted_meta']['submitted_at'];

		$application = Application::create([
			'reference_number' => $sequence->next(),
			'status' => Status::Opened,
			'flagged' => false,
			'opened_at' => $submittedAt,
			'last_changed_at' => $submittedAt,
			'shares_apartment' => $data['shares_apartment'],
			'submission_id' => $data['submission_id'],
			'submitted_ip' => $data['submitted_meta']['ip'],
			'submitted_user_agent' => $data['submitted_meta']['user_agent'],

			// Housing wish (inlined)
			'wants_balcony' => $housing['wants_balcony'] ?? null,
			'wants_elevator' => $housing['wants_elevator'] ?? null,
			'max_gross_rent' => $housing['max_gross_rent'],
			'earliest_move_in' => $housing['earliest_move_in'],

			// Household info (inlined)
			'total_persons' => $household['total_persons'],
			'adults_count' => $household['adults_count'],
			'children_count' => $household['children_count'],
			'all_children_live_constantly' => $household['all_children_live_constantly'] ?? null,
			'plays_music' => $household['plays_music'],
			'musical_instruments' => $household['musical_instruments'] ?? null,
			'has_pets' => $household['has_pets'],
			'pets_description' => $household['pets_description'] ?? null,
			'remarks' => $household['remarks'] ?? null,
		]);

		(new UpsertApplicant())->execute($application, $data['main_applicant'], role: 'main_applicant', position: 1);

		if (! empty($data['co_applicant'])) {
			(new UpsertApplicant())->execute($application, $data['co_applicant'], role: 'co_applicant', position: 2);
		}

		(new SyncHousing())->execute($application, $housing);
		(new SyncChildren())->execute($application, $data['children'] ?? []);

		(new RecordStatus())->execute(
			application: $application,
			to: Status::Opened,
			from: null,
			actorUserId: null,
			reason: null,
			occurredAt: $submittedAt instanceof \Carbon\CarbonInterface ? $submittedAt : \Carbon\Carbon::parse($submittedAt),
			isInitial: true,
		);

		return $application;
	}
}
