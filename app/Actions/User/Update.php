<?php

namespace App\Actions\User;

use App\Models\User;

class Update
{
	public function execute(User $user, array $data): User
	{
		if (empty($data['password'])) {
			unset($data['password']);
		}

		$user->update($data);

		return $user->fresh();
	}
}
