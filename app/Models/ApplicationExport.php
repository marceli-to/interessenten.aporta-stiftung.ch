<?php

namespace App\Models;

use App\Enums\ExportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationExport extends Model
{
	use HasFactory;

	protected $guarded = [];

	protected function casts(): array
	{
		return [
			'status' => ExportStatus::class,
			'application_count' => 'integer',
			'expires_at' => 'datetime',
		];
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function isReady(): bool
	{
		return $this->status === ExportStatus::Ready;
	}

	public function isExpired(): bool
	{
		return $this->expires_at !== null && $this->expires_at->isPast();
	}

	public function isDownloadable(): bool
	{
		return $this->isReady() && $this->path !== null && ! $this->isExpired();
	}
}
