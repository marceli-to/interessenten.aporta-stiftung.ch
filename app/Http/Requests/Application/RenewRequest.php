<?php

namespace App\Http\Requests\Application;

/**
 * Updated data from the public form in renewal mode. Same sections and rules
 * as the intake (StoreRequest), minus the intake-only fields: the application
 * already exists, so there is no submission_id / submitted_meta.
 */
class RenewRequest extends StoreRequest
{
	public function rules(): array
	{
		return array_diff_key(parent::rules(), array_flip([
			'submission_id',
			'submitted_meta',
			'submitted_meta.ip',
			'submitted_meta.user_agent',
			'submitted_meta.submitted_at',
		]));
	}
}
