<?php

namespace App\Models;

use App\Enums\IncomeBracket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Employer extends Model
{
	use HasFactory, LogsActivity;

	protected $guarded = [];

	protected function casts(): array
	{
		return [
			'annual_income_bracket_slug' => IncomeBracket::class,
		];
	}

	public function applicant(): BelongsTo
	{
		return $this->belongsTo(Applicant::class);
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly(['name', 'workload_percent', 'annual_income_bracket_slug'])
			->logOnlyDirty()
			->dontLogEmptyChanges();
	}
}
