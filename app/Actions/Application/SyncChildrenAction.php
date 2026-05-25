<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\Child;

class SyncChildrenAction
{
	public function execute(Application $application, array $children): void
	{
		Child::where('application_id', $application->id)->delete();

		foreach ($children as $child) {
			Child::create([
				'application_id' => $application->id,
				'position' => $child['position'],
				'birth_year' => $child['birth_year'],
			]);
		}
	}
}
