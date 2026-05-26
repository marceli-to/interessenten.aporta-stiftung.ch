<?php

namespace App\Actions\User;

use App\Models\User;

class Delete
{
	public function execute(User $user): void
	{
		$user->delete();
	}
}
