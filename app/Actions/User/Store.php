<?php

namespace App\Actions\User;

use App\Models\User;

class Store
{
	public function execute(array $data): User
	{
		$data['role'] ??= 'admin';
		$data['active'] ??= true;
		$data['email_verified_at'] = now();

		return User::create($data);
	}
}
