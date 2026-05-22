<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class HousingWish extends Model
{
	use HasFactory, LogsActivity;

	protected $guarded = [];

	protected function casts(): array
	{
		return [
			'wants_balcony' => 'boolean',
			'wants_elevator' => 'boolean',
			'max_gross_rent' => 'decimal:2',
			'earliest_move_in' => 'date',
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
				'wants_balcony', 'wants_elevator', 'max_gross_rent', 'earliest_move_in',
				'property_group', 'property_class',
			])
			->logOnlyDirty()
			->dontLogEmptyChanges();
	}
}
