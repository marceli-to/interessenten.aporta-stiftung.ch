<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Application extends Model
{
	use HasFactory, LogsActivity, SoftDeletes;

	protected $guarded = [];

	protected function casts(): array
	{
		return [
			'status' => Status::class,
			'flagged' => 'boolean',
			'shares_apartment' => 'boolean',
			'opened_at' => 'datetime',
			'extended_at' => 'datetime',
			'archived_at' => 'datetime',
			'last_changed_at' => 'datetime',
		];
	}

	public function applicants(): HasMany
	{
		return $this->hasMany(Applicant::class);
	}

	public function mainApplicant(): HasOne
	{
		return $this->hasOne(Applicant::class)->where('role', 'main_applicant');
	}

	public function coApplicants(): HasMany
	{
		return $this->hasMany(Applicant::class)->where('role', 'co_applicant');
	}

	public function housingWish(): HasOne
	{
		return $this->hasOne(HousingWish::class);
	}

	public function householdInfo(): HasOne
	{
		return $this->hasOne(HouseholdInfo::class);
	}

	public function children(): HasMany
	{
		return $this->hasMany(Child::class);
	}

	public function notes(): HasMany
	{
		return $this->hasMany(Note::class);
	}

	public function statusEvents(): HasMany
	{
		return $this->hasMany(StatusEvent::class);
	}

	public function owner(): BelongsTo
	{
		return $this->belongsTo(User::class, 'owner_user_id');
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly(['status', 'flagged', 'owner_user_id', 'archived_at', 'extended_at'])
			->logOnlyDirty()
			->dontLogEmptyChanges();
	}
}
