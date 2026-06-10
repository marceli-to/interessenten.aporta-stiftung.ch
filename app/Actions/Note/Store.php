<?php

namespace App\Actions\Note;

use App\Models\Application;
use App\Models\Note;
use App\Models\User;

class Store
{
	public function execute(Application $application, array $data, User $author): Note
	{
		$note = $application->notes()->create([
			'body' => $data['body'],
			'user_id' => $author->id,
		]);

		// NoteResource renders the author from the user relation; set it from the
		// acting user so the response needs no extra query.
		$note->setRelation('user', $author);

		return $note;
	}
}
