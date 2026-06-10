<?php

namespace App\Actions\Note;

use App\Models\Note;

class Update
{
	public function execute(Note $note, array $data): Note
	{
		$note->update($data);

		// Reload with the author relation for NoteResource.
		return $note->fresh('user');
	}
}
