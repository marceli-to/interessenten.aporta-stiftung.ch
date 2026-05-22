<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Note extends Model
{
	use HasFactory, LogsActivity;

	protected $guarded = [];

	protected function casts(): array
	{
		return [
			'important' => 'boolean',
		];
	}

	public function application(): BelongsTo
	{
		return $this->belongsTo(Application::class);
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly(['body', 'important'])
			->logOnlyDirty()
			->dontLogEmptyChanges();
	}
}
