<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Cross-Origin Resource Sharing (CORS) Configuration
	|--------------------------------------------------------------------------
	|
	| Only `/api/v1/lookups` is exposed cross-origin so the Statamic form can
	| fetch reference data directly from the visitor's browser. Other API
	| routes (`/api/v1/applications`, etc.) are same-origin only and the
	| server-to-server intake uses a bearer key, not cookies.
	|
	*/

	'paths' => ['api/v1/lookups'],

	'allowed_methods' => ['GET', 'HEAD', 'OPTIONS'],

	'allowed_origins' => [env('APORTA_STATAMIC_ORIGIN', 'https://aporta-stiftung.ch')],

	'allowed_origins_patterns' => [],

	'allowed_headers' => ['Accept', 'Content-Type', 'If-None-Match'],

	'exposed_headers' => ['ETag'],

	'max_age' => 86400,

	'supports_credentials' => false,

];
