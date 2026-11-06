<?php

namespace App\Models;

use App\Enums\Status;
use Carbon\CarbonInterface;
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
			'renewal_link_sent_at' => 'datetime',
			'last_changed_at' => 'datetime',

			'wants_elevator' => 'boolean',
			'max_gross_rent' => 'decimal:2',
			'earliest_move_in' => 'date',

			'total_persons' => 'integer',
			'adults_count' => 'integer',
			'children_count' => 'integer',
			'all_children_live_constantly' => 'boolean',
			'has_pets' => 'boolean',
		];
	}

	protected static function booted(): void
	{
		// An extension starts a new validity cycle and invalidates the renewal
		// links of the previous one, so "link sent" no longer applies.
		static::saving(function (Application $application) {
			if ($application->isDirty('extended_at')) {
				$application->renewal_link_sent_at = null;
			}
		});
	}

	/**
	 * The date the validity window is counted from: extended_at when the
	 * application was renewed, otherwise opened_at.
	 */
	public function renewalReferenceDate(): CarbonInterface
	{
		return $this->extended_at ?? $this->opened_at;
	}

	public function validUntil(): CarbonInterface
	{
		return $this->renewalReferenceDate()->copy()->addMonths(
			(int) config('aporta.lifecycle.validity_months', 6)
		);
	}

	/**
	 * Whether the applicant can confirm, update or delete the application via a
	 * renewal link: open, extended or archived (Kundenvorgabe: an archived
	 * application comes back as Verlängert), as long as it is not deleted.
	 * KNIF is excluded.
	 */
	public function isRenewable(): bool
	{
		return in_array($this->status, [Status::Opened, Status::Extended, Status::Archived], true)
			&& ! $this->trashed();
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
				'status', 'archived_at', 'extended_at', 'renewal_link_sent_at',
				'wants_elevator', 'max_gross_rent', 'earliest_move_in',
				'property_group', 'property_class',
				'total_persons', 'adults_count', 'children_count',
				'has_pets', 'remarks',
			])
			->logOnlyDirty()
			->dontLogEmptyChanges();
	}
}
