<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIntakeApiKey
{
	public function handle(Request $request, Closure $next): Response
	{
		$header = (string) $request->header('Authorization', '');

		if (! preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
			abort(401, 'Missing or malformed Authorization header.');
		}

		$expected = (string) config('aporta.intake_api_key_hash');

		if ($expected === '') {
			abort(500, 'Intake API key is not configured.');
		}

		$presented = hash('sha256', $matches[1]);

		if (! hash_equals($expected, $presented)) {
			abort(403, 'Invalid intake API key.');
		}

		return $next($request);
	}
}
