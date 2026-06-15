<?php

return [
	'intake_api_key_hash' => env('APORTA_INTAKE_API_KEY_HASH'),

	'reference_number_start' => (int) env('APORTA_REFERENCE_NUMBER_START', 1),

	'new_application_notify_email' => env('APORTA_NEW_APPLICATION_NOTIFY_EMAIL'),

	'statamic_origin' => env('APORTA_STATAMIC_ORIGIN', 'https://aporta-stiftung.ch'),

	'exports' => [
		// Where generated export files are stored. Defaults to the app's default
		// disk (local in dev); set to an S3 disk in production.
		'disk' => env('APORTA_EXPORT_DISK', env('FILESYSTEM_DISK', 'local')),

		// How long a generated export stays downloadable before the scheduled
		// cleanup removes the file and tracking row.
		'ttl_hours' => (int) env('APORTA_EXPORT_TTL_HOURS', 24),
	],
];
