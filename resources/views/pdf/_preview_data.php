<?php

/**
 * Realistische Dummy-Daten + eingebettete Assets für die PDF-Layout-Vorschau.
 *
 * NUR für die Entwicklungs-Vorschau (Route /dev/pdf-vorschau, local only).
 * Spiegelt die View-Datenform von resources/views/pdf/application.blade.php:
 * Labels sind bereits aufgelöst (keine Enum-Slugs), genau wie sie
 * App\Actions\Application\Pdf\Present für den echten Export liefert.
 *
 * @return array{fonts:array, generatedAt:\Carbon\CarbonInterface, a:array}
 */

return [
    'fonts' => \App\Actions\Application\Pdf\Assets::fonts(),
    'generatedAt' => now(),
    'a' => [
        'reference_number' => 1042,
        'status' => 'Eröffnet',
        'opened_at' => '22.05.2026',
        'last_changed_at' => '14.06.2026',
        'shares_apartment' => false,

        'housing_wish' => [
            'max_gross_rent' => "CHF 1'800.–",
            'earliest_move_in' => '01.09.2026',
            'wants_balcony' => true,
            'wants_elevator' => false,
            'districts' => ['Kreis 6', 'Kreis 7', 'Kreis 8'],
            'floors' => ['Hochparterre', '1. OG', '2. OG'],
            'rooms' => ['3 Zimmer', '3.5 Zimmer'],
        ],

        'household_info' => [
            'total_persons' => 3,
            'adults_count' => 2,
            'children_count' => 1,
            'all_children_live_constantly' => true,
            'plays_music' => true,
            'musical_instruments' => 'Klavier, Querflöte',
            'has_pets' => false,
            'pets_description' => null,
            'remarks' => 'Wir sind eine ruhige, naturverbundene Familie und seit Jahren in Zürich verwurzelt. Eine längerfristige Mietdauer ist uns ein grosses Anliegen.',
        ],

        'applicants' => [
            [
                'name' => 'Anna Muster-Bianchi',
                'role' => 'Hauptbewerberin',
                'birth_date' => '12.03.1988',
                'marital_status' => 'Verheiratet',
                'nationality' => 'Schweiz',
                'place_of_origin' => 'Chur GR',
                'residence_permit' => null,
                'swiss_residence_since' => null,
                'address' => 'Seefeldstrasse 142, 8008 Zürich',
                'mobile_phone' => '079 123 45 67',
                'landline_phone' => '044 380 12 34',
                'email' => 'anna.muster@example.ch',
                'relationship_to_main' => null,
                'occupation' => 'Primarlehrerin',
                'employment_status' => 'Festanstellung',
                'debt_enforcement_last_2y' => false,
                'employer' => [
                    'name' => 'Schule Kreis Letzi, Stadt Zürich',
                    'workload_percent' => '80 %',
                    'annual_income_bracket' => "CHF 80'000 – 100'000",
                ],
                'current_housing' => [
                    'tenant_role' => 'Hauptmieterin',
                    'terminated_by_landlord' => true,
                    'termination_reason' => 'Eigenbedarf der Vermieterschaft (Sanierung der Liegenschaft).',
                    'rent_duration' => '6 – 10 Jahre',
                    'landlord_name' => 'Immobilien Seefeld AG',
                    'landlord_contact_person' => 'Herr T. Brunner',
                    'landlord_phone' => '044 555 66 77',
                ],
            ],
            [
                'name' => 'Marco Bianchi',
                'role' => 'Mitbewerber',
                'birth_date' => '04.07.1985',
                'marital_status' => 'Verheiratet',
                'nationality' => 'Italien',
                'place_of_origin' => null,
                'residence_permit' => 'C',
                'swiss_residence_since' => '01.10.2009',
                'address' => 'gleich wie Hauptbewerberin',
                'mobile_phone' => '078 987 65 43',
                'landline_phone' => null,
                'email' => 'm.bianchi@example.ch',
                'relationship_to_main' => 'Ehepartner',
                'occupation' => 'Bauingenieur ETH',
                'employment_status' => 'Festanstellung',
                'debt_enforcement_last_2y' => false,
                'employer' => [
                    'name' => 'Hartmann + Partner Bauingenieure AG',
                    'workload_percent' => '100 %',
                    'annual_income_bracket' => "CHF 100'000 – 130'000",
                ],
                'current_housing' => null,
            ],
        ],

        'children' => [
            ['birth_year' => 2017],
        ],

        'notes' => [
            [
                'body' => 'Telefonisch Rückfrage zur Mietdauer geklärt – Familie sucht ausdrücklich langfristig.',
                'author' => 'R. Keller',
                'created_at' => '03.06.2026 14:20',
                'important' => true,
            ],
            [
                'body' => 'Unterlagen vollständig eingegangen.',
                'author' => 'R. Keller',
                'created_at' => '23.05.2026 09:05',
                'important' => false,
            ],
        ],

        'status_events' => [
            ['from' => null, 'to' => 'Eröffnet', 'actor' => 'System', 'occurred_at' => '22.05.2026 18:42'],
            ['from' => 'Eröffnet', 'to' => 'Verlängert', 'actor' => 'R. Keller', 'occurred_at' => '05.06.2026 11:10'],
        ],
    ],
];
