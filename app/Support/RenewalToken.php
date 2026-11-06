<?php

namespace App\Support;

use App\Models\Application;

/**
 * Token in the renewal links: "{id}.{cycle}.{signature}".
 *
 * {cycle} is the timestamp of the application's current reference date
 * (extended_at ?? opened_at). A token only resolves while that date is
 * unchanged, so confirming or updating (which stamps a new extended_at)
 * invalidates every link of the previous cycle without storing anything.
 * The signature is an HMAC over id + cycle with the app key.
 */
class RenewalToken
{
	public static function for(Application $application): string
	{
		$cycle = $application->renewalReferenceDate()->getTimestamp();

		return $application->id.'.'.$cycle.'.'.self::sign($application->id, $cycle);
	}

	/**
	 * Resolve a token to its application, or null when the token is malformed,
	 * tampered with, from an earlier cycle, or the application can no longer be
	 * renewed (KNIF, deleted).
	 */
	public static function resolve(?string $token): ?Application
	{
		if (! is_string($token) || ! preg_match('/^(\d+)\.(\d+)\.([a-f0-9]{40})$/', $token, $m)) {
			return null;
		}

		[$id, $cycle, $signature] = [(int) $m[1], (int) $m[2], $m[3]];

		if (! hash_equals(self::sign($id, $cycle), $signature)) {
			return null;
		}

		$application = Application::find($id);

		if (! $application
			|| $application->renewalReferenceDate()->getTimestamp() !== $cycle
			|| ! $application->isRenewable()) {
			return null;
		}

		return $application;
	}

	private static function sign(int $id, int $cycle): string
	{
		return substr(hash_hmac('sha256', "renewal|{$id}|{$cycle}", (string) config('app.key')), 0, 40);
	}
}
