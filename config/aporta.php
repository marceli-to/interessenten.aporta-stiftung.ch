<?php

return [
	'intake_api_key_hash' => env('APORTA_INTAKE_API_KEY_HASH'),

	'reference_number_start' => (int) env('APORTA_REFERENCE_NUMBER_START', 1),

	'new_application_notify_email' => env('APORTA_NEW_APPLICATION_NOTIFY_EMAIL'),

	'statamic_origin' => env('APORTA_STATAMIC_ORIGIN', 'https://aporta-stiftung.ch'),

	'exports' => [
		// Upper bound for the synchronous PDF download. Larger selections are
		// rejected (the user narrows the filter) so a request never renders for so
		// long it risks a timeout.
		'max_sync' => (int) env('APORTA_EXPORT_MAX_SYNC', 100),
	],
];
