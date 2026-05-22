<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceMaxBodySize
{
	public function handle(Request $request, Closure $next, int $maxBytes = 131072): Response
	{
		$declared = (int) $request->header('Content-Length', 0);
		if ($declared > $maxBytes) {
			abort(413, 'Request body too large.');
		}

		if (strlen($request->getContent()) > $maxBytes) {
			abort(413, 'Request body too large.');
		}

		return $next($request);
	}
}
