<?php

namespace App\Providers;

use App\Models\Applicant;
use App\Models\Child;
use App\Models\CurrentHousing;
use App\Models\Employer;
use App\Models\Note;
use App\Observers\TouchesApplicationObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
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

		RateLimiter::for('intake', function (Request $request) {
			return Limit::perMinute(120)->by($request->ip());
		});

		if (! $this->app->environment('production') && ($alwaysTo = env('MAIL_TO'))) {
			Mail::alwaysTo($alwaysTo);
		}
	}
}
