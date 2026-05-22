<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusEvent extends Model
{
	use HasFactory;

	public $timestamps = false;

	protected $guarded = [];

	protected function casts(): array
	{
		return [
			'from_status' => Status::class,
			'to_status' => Status::class,
			'occurred_at' => 'datetime',
		];
	}

	public function application(): BelongsTo
	{
		return $this->belongsTo(Application::class);
	}

	public function actor(): BelongsTo
	{
		return $this->belongsTo(User::class, 'actor_user_id');
	}
}
