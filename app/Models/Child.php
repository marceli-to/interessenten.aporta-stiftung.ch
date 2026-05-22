<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Child extends Model
{
	use HasFactory, LogsActivity;

	protected $guarded = [];

	public function application(): BelongsTo
	{
		return $this->belongsTo(Application::class);
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly(['position', 'birth_year'])
			->logOnlyDirty()
			->dontLogEmptyChanges();
	}
}
