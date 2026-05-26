<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\User\Delete;
use App\Actions\User\Store;
use App\Actions\User\Update;
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

	public function store(StoreRequest $request, Store $action)
	{
		$user = $action->execute($request->validated());

		return (new UserResource($user))->response()->setStatusCode(201);
	}

	public function show(User $user)
	{
		return new UserResource($user);
	}

	public function update(UpdateRequest $request, User $user, Update $action)
	{
		$user = $action->execute($user, $request->validated());

		return new UserResource($user);
	}

	public function destroy(User $user, Delete $action)
	{
		$action->execute($user);

		return response()->json(null, 204);
	}
}
