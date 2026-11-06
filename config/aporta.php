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

	'lifecycle' => [
		// Open/extended applications auto-archive this long after their reference
		// date (extended_at when set, otherwise opened_at): 6 months validity +
		// 3 months grace ("Kulanz").
		'archive_after_months' => (int) env('APORTA_ARCHIVE_AFTER_MONTHS', 9),

		// Archived applications are soft-deleted (recoverable via the "Gelöscht"
		// view) this long after archived_at.
		'delete_after_months' => (int) env('APORTA_DELETE_AFTER_MONTHS', 3),

		// An application is valid this long after its reference date. The
		// renewal link email states the end of this window.
		'validity_months' => (int) env('APORTA_VALIDITY_MONTHS', 6),
	],

	'renewal' => [
		// Prefilled application form on the public website. The renewal token is
		// appended as ?t=…
		'form_url' => env('APORTA_RENEWAL_FORM_URL', 'https://www.aporta-stiftung.ch/liegenschaften/wohnungsbewerbung-formular'),
	],
];
