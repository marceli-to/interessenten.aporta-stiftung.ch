<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\Note\Delete as DeleteNote;
use App\Actions\Note\Store as StoreNote;
use App\Actions\Note\Update as UpdateNote;
use App\Http\Controllers\Controller;
use App\Http\Requests\Note\StoreRequest;
use App\Http\Requests\Note\UpdateRequest;
use App\Http\Resources\NoteResource;
use App\Models\Application;
use App\Models\Note;

class NoteController extends Controller
{
	public function store(StoreRequest $request, Application $application)
	{
		$note = (new StoreNote())->execute($application, $request->validated(), $request->user());

		return (new NoteResource($note))->response()->setStatusCode(201);
	}

	public function update(UpdateRequest $request, Application $application, Note $note)
	{
		$note = (new UpdateNote())->execute($note, $request->validated());

		return new NoteResource($note);
	}

	public function destroy(Application $application, Note $note)
	{
		(new DeleteNote())->execute($note);

		return response()->json(null, 204);
	}
}
