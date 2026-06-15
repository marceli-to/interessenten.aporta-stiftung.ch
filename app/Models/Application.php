<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
			'shares_apartment' => 'boolean',
			'opened_at' => 'datetime',
			'extended_at' => 'datetime',
			'archived_at' => 'datetime',
			'last_changed_at' => 'datetime',

			'max_gross_rent' => 'decimal:2',
			'earliest_move_in' => 'date',

			'total_persons' => 'integer',
			'adults_count' => 'integer',
			'children_count' => 'integer',
			'all_children_live_constantly' => 'boolean',
			'has_pets' => 'boolean',
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

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly([
				'status', 'archived_at', 'extended_at',
				'max_gross_rent', 'earliest_move_in',
				'property_group', 'property_class',
				'total_persons', 'adults_count', 'children_count',
				'has_pets', 'remarks',
			])
			->logOnlyDirty()
			->dontLogEmptyChanges();
	}
}
