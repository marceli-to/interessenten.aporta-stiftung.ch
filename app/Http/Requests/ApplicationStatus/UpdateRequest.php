<?php

namespace App\Http\Requests\ApplicationStatus;

use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Status transition from the dashboard. `reason` is an optional audit note;
 * `extended_at` / `archived_at` carry the transition date the Info panel reveals
 * for Verlängert / Archiviert. The validated input is exposed through small
 * accessors so the controller hands the Status\Transition action ready-to-use
 * values and never parses the request itself.
 */
class UpdateRequest extends FormRequest
{
	public function authorize(): bool
	{
		// Route is behind the `auth` middleware; matches the rest of the dashboard
		// (no per-role policy yet).
		return true;
	}

	public function rules(): array
	{
		return [
			'status' => ['required', Rule::enum(Status::class)],
			'reason' => ['nullable', 'string', 'max:255'],
			'extended_at' => ['nullable', 'date'],
			'archived_at' => ['nullable', 'date'],
		];
	}

	public function status(): Status
	{
		return Status::from($this->validated('status'));
	}

	public function reason(): ?string
	{
		return $this->validated('reason');
	}

	/**
	 * Transition date for the target state, keyed by the attribute that holds it
	 * ('extended_at' for Verlängert, 'archived_at' for Archiviert). Returns an
	 * empty array when the client does not supply the field, so the action leaves
	 * the column untouched rather than nulling it. Any other target stamps nothing.
	 */
	public function transitionDate(): array
	{
		$field = match ($this->status()) {
			Status::Extended => 'extended_at',
			Status::Archived => 'archived_at',
			default => null,
		};

		if ($field === null || ! $this->has($field)) {
			return [];
		}

		return [$field => $this->validated($field)];
	}
}
