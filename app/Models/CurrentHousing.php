<?php

namespace App\Models;

use App\Enums\RentDuration;
use App\Enums\TenantRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CurrentHousing extends Model
{
	use HasFactory, LogsActivity;

	protected $guarded = [];

	protected function casts(): array
	{
		return [
			'tenant_role' => TenantRole::class,
			'rent_duration_slug' => RentDuration::class,
			'terminated_by_landlord' => 'boolean',
		];
	}

	public function applicant(): BelongsTo
	{
		return $this->belongsTo(Applicant::class);
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly([
				'tenant_role', 'terminated_by_landlord', 'termination_reason',
				'landlord_name', 'rent_duration_slug',
			])
			->logOnlyDirty()
			->dontLogEmptyChanges();
	}
}
