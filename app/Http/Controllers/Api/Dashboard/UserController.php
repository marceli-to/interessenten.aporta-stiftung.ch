<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\User\Delete as DeleteUser;
use App\Actions\User\Store as StoreUser;
use App\Actions\User\Update as UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;

class UserController extends Controller
{
	public function index()
	{
		return UserResource::collection(
			User::orderBy('name')->get()
		);
	}

	public function store(StoreRequest $request)
	{
		$user = (new StoreUser())->execute($request->validated());

		return (new UserResource($user))->response()->setStatusCode(201);
	}

	public function show(User $user)
	{
		return new UserResource($user);
	}

	public function update(UpdateRequest $request, User $user)
	{
		$user = (new UpdateUser())->execute($user, $request->validated());

		return new UserResource($user);
	}

	public function destroy(User $user)
	{
		(new DeleteUser())->execute($user);

		return response()->json(null, 204);
	}
}
