<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'body' => $this->body,
			'author' => $this->user?->full_name ?? '–',
			'created_at' => $this->created_at?->toIso8601String(),
		];
	}
}
