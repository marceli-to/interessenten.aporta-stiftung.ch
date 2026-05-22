<?php

namespace App\Providers;

use App\Models\Applicant;
use App\Models\Child;
use App\Models\CurrentHousing;
use App\Models\Employer;
use App\Models\Note;
use App\Observers\TouchesApplicationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		//
	}

	public function boot(): void
	{
		foreach ([Applicant::class, Child::class, Note::class] as $model) {
			$model::observe(TouchesApplicationObserver::class);
		}

		// Employer and CurrentHousing hang off Applicant; touch via their applicant relationship.
		foreach ([Employer::class, CurrentHousing::class] as $model) {
			$model::observe(TouchesApplicationObserver::class);
		}
	}
}
