<?php

namespace App\Actions\Application;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Child;
use App\Models\CurrentHousing;
use App\Models\Employer;
use App\Models\Note;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class Erase
{
	public function __construct(
		private Delete $delete = new Delete(),
	) {}

	/**
	 * Permanently delete an application at the applicant's request: force-delete
	 * the aggregate (FK cascade) and purge the activity log entries of every row
	 * in it, which still carry personal data (names, email, address).
	 *
	 * Nothing is kept except the log line the caller writes with the reference
	 * number.
	 */
	public function execute(Application $application): void
	{
		DB::transaction(function () use ($application) {
			$applicantIds = Applicant::where('application_id', $application->id)->pluck('id');

			$subjects = [
				(new Application())->getMorphClass() => collect([$application->id]),
				(new Applicant())->getMorphClass() => $applicantIds,
				(new Employer())->getMorphClass() => Employer::whereIn('applicant_id', $applicantIds)->pluck('id'),
				(new CurrentHousing())->getMorphClass() => CurrentHousing::whereIn('applicant_id', $applicantIds)->pluck('id'),
				(new Child())->getMorphClass() => Child::where('application_id', $application->id)->pluck('id'),
				(new Note())->getMorphClass() => Note::where('application_id', $application->id)->pluck('id'),
			];

			$this->delete->execute($application, force: true);

			// After the delete: the force delete itself logs a "deleted" entry.
			foreach ($subjects as $type => $ids) {
				if ($ids->isNotEmpty()) {
					Activity::where('subject_type', $type)->whereIn('subject_id', $ids)->delete();
				}
			}
		});
	}
}
