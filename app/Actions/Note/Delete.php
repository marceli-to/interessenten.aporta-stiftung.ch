<?php

namespace App\Actions\Note;

use App\Models\Note;

class Delete
{
	public function execute(Note $note): void
	{
		$note->delete();
	}
}
