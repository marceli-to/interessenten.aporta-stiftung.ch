<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Enums\Nationality;
use App\Enums\RelationshipToMain;
use App\Enums\ResidencePermit;
use App\Enums\Salutation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Applicant extends Model
{
	use HasFactory, LogsActivity;

	protected $guarded = [];

	protected function casts(): array
	{
		return [
			'salutation' => Salutation::class,
			'marital_status' => MaritalStatus::class,
			'nationality' => Nationality::class,
			'residence_permit' => ResidencePermit::class,
			'employment_status' => EmploymentStatus::class,
			'relationship_to_main' => RelationshipToMain::class,
			'birth_date' => 'date',
			'swiss_residence_since' => 'date',
			'same_address_as_main' => 'boolean',
			'debt_enforcement_last_2y' => 'boolean',
		];
	}

	public function application(): BelongsTo
	{
		return $this->belongsTo(Application::class);
	}

	public function employer(): HasOne
	{
		return $this->hasOne(Employer::class);
	}

	public function currentHousing(): HasOne
	{
		return $this->hasOne(CurrentHousing::class);
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly([
				'first_name', 'last_name', 'email', 'mobile_phone',
				'street', 'street_number', 'postal_code', 'city',
				'employment_status', 'marital_status', 'nationality',
			])
			->logOnlyDirty()
			->dontLogEmptyChanges();
	}
}
