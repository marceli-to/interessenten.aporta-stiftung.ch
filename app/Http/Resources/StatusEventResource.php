<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusEventResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'status' => [
				'value' => $this->to_status->value,
				'label' => $this->to_status->label(),
			],
			// Null for the intake event (no actor); the panel renders that as
			// "Über Webformular". Back-office transitions carry the acting user.
			'actor' => $this->actor?->full_name,
			'occurred_at' => $this->occurred_at?->toIso8601String(),
		];
	}
}
