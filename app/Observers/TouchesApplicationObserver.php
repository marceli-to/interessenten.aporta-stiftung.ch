<?php

namespace App\Observers;

use App\Models\Application;
use Illuminate\Database\Eloquent\Model;

/**
 * Observers that touch the parent Application's `last_changed_at` whenever a
 * nested aggregate row is saved/deleted. Wired in AppServiceProvider.
 */
class TouchesApplicationObserver
{
	public function saved(Model $model): void
	{
		$this->touch($model);
	}

	public function deleted(Model $model): void
	{
		$this->touch($model);
	}

	private function touch(Model $model): void
	{
		$applicationId = match (true) {
			isset($model->application_id) => $model->application_id,
			isset($model->applicant) => $model->applicant?->application_id,
			default => null,
		};

		if (! $applicationId) {
			return;
		}

		Application::withoutEvents(function () use ($applicationId) {
			Application::query()
				->whereKey($applicationId)
				->update(['last_changed_at' => now()]);
		});
	}
}
