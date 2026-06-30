<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Real Aporta staff accounts (production-safe, idempotent).
 *
 * Laura Cerny is the responsible person at the Stiftung and the author the legacy
 * import attributes every imported note to (see docs/legacy-import.md). Created here
 * with an unusable random password — she sets a real one via the password-reset flow.
 */
class AportaUserSeeder extends Seeder
{
	/** Email the legacy import looks up to attribute imported notes. */
	public const NOTE_AUTHOR_EMAIL = 'laura.cerny@aporta-stiftung.ch';

	public function run(): void
	{
		User::firstOrCreate(
			['email' => self::NOTE_AUTHOR_EMAIL],
			[
				'firstname' => 'Laura',
				'name' => 'Cerny',
				'password' => Str::random(40),
			],
		);
	}
}
