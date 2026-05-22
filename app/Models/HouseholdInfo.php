<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class HouseholdInfo extends Model
{
	use HasFactory, LogsActivity;

	protected $table = 'household_info';

	protected $guarded = [];

	protected function casts(): array
	{
		return [
			'total_persons' => 'integer',
			'adults_count' => 'integer',
			'children_count' => 'integer',
			'all_children_live_constantly' => 'boolean',
			'plays_music' => 'boolean',
			'has_pets' => 'boolean',
		];
	}

	public function application(): BelongsTo
	{
		return $this->belongsTo(Application::class);
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly([
				'total_persons', 'adults_count', 'children_count',
				'plays_music', 'has_pets', 'remarks',
			])
			->logOnlyDirty()
			->dontLogEmptyChanges();
	}
}
