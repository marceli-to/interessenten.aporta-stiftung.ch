<?php

namespace App\Support\Legacy;

use App\Enums\Room;

/**
 * Code/value legends for the legacy data import (storage/import/*.json).
 * Decisions and evidence: docs/legacy-import.md. Shared by the dry-run report and
 * the real import so both speak the same dialect. Every mapper returns null on an
 * unknown/empty input so callers can flag it rather than silently coerce.
 */
class LegacyMaps
{
	/** Old top-level `status` code → Status enum value. */
	public const STATUS = [
		'0' => 'opened',
		'1' => 'opened',
		'2' => 'extended',
		'10' => 'extended',
		'6' => 'archived',
		'8' => 'archived',
		'5' => 'knif',
	];

	/** XML MARITAL_STATUS code → MaritalStatus enum value (enum order; 0/empty = unknown). */
	public const MARITAL = [
		'1' => 'single',
		'2' => 'married',
		'3' => 'divorced',
		'4' => 'widowed',
		'5' => 'dissolved_partnership',
		'6' => 'registered_partnership',
	];

	/** XML EMPLOYMENT_SITUATION code → EmploymentStatus enum value (5 & 6 both retired). */
	public const EMPLOYMENT = [
		'1' => 'employed',
		'2' => 'student',
		'3' => 'self_employed',
		'4' => 'homemaker',
		'5' => 'retired',
		'6' => 'retired',
		'7' => 'unemployed',
	];

	/** XML ANNUAL_INCOME code 1..12 → IncomeBracket value, low→high (0/empty = unknown). */
	public const INCOME = [
		'1' => 'less_than_20k',
		'2' => '20k_30k',
		'3' => '30k_40k',
		'4' => '40k_50k',
		'5' => '50k_60k',
		'6' => '60k_70k',
		'7' => '70k_80k',
		'8' => '80k_90k',
		'9' => '90k_100k',
		'10' => '100k_120k',
		'11' => '120k_140k',
		'12' => 'more_than_140k',
	];

	/** XML CURRENT_RENT/TENANT_ROLE code → TenantRole value (0/empty = no current tenancy). */
	public const TENANT_ROLE = [
		'1' => 'main_tenant',
		'2' => 'subtenant',
	];

	/** Salutation label (top-level JSON or XML) → Salutation value. "Ander" is a literal value in the data. */
	public const SALUTATION = [
		'Herr' => 'herr',
		'Frau' => 'frau',
		'Andere' => 'other',
		'Ander' => 'other',
	];

	/**
	 * Country names (German, incl. the typos found in the data) → ISO 3166-1 alpha-2.
	 * Complete for every non-ISO value in the export except "Andere", which carries no
	 * country and is left null for the import to decide a fallback.
	 */
	public const NATIONALITY_NAMES = [
		'Schweiz' => 'CH',
		'Deutschland' => 'DE',
		'Italien' => 'IT',
		'Portugal' => 'PT',
		'Spanien' => 'ES',
		'Frankreich' => 'FR',
		'Österreich' => 'AT',
		'Kroatien' => 'HR',
		'Türkei' => 'TR',
		'Serbien' => 'RS',
		'Griechenland' => 'GR',
		'Ukraine' => 'UA',
		'Kolumbien' => 'CO',
		'Eritrea' => 'ER',
		'Niederlande' => 'NL',
		'Belgien' => 'BE',
		'Russland' => 'RU',
		'Vereinigte Staaten' => 'US',
		'Vereinigtes Königreich' => 'GB',
		'Grossbritanien' => 'GB',
		'Volksrepublik China' => 'CN',
		'Albanien' => 'AL',
		'Schweden' => 'SE',
		'Dänemark' => 'DK',
		'Mexiko' => 'MX',
		'Afghanistan' => 'AF',
		'Neuseeland' => 'NZ',
		'Mazedonien' => 'MK',
		'Bosnien und Herzegowina' => 'BA',
		'Bulgarien' => 'BG',
		'Senegal' => 'SN',
		'Peru' => 'PE',
		'Sri Lanka' => 'LK',
		'Tschechien' => 'CZ',
		'Algerien' => 'DZ',
		'Südafrika' => 'ZA',
		'Luxemburg' => 'LU',
		'Chile' => 'CL',
		'Syrien' => 'SY',
		'Kanada' => 'CA',
		'Indonesien' => 'ID',
		'Sudan' => 'SD',
		'Liechtenstein' => 'LI',
		'Georgien' => 'GE',
		'Ungarn' => 'HU',
		'Slowakei' => 'SK',
		'Slowenien' => 'SI',
		'Slovenien' => 'SI',
		'Kongo, Demokratische Republik' => 'CD',
		'Thailand' => 'TH',
		'Somalia' => 'SO',
		'Dominikanische Republik' => 'DO',
		'Lettland' => 'LV',
		'Norwegen' => 'NO',
		'Usbekistan' => 'UZ',
	];

	public static function status(string $code): ?string
	{
		return self::STATUS[trim($code)] ?? null;
	}

	public static function marital(string $code): ?string
	{
		return self::MARITAL[trim($code)] ?? null;
	}

	public static function employment(string $code): ?string
	{
		return self::EMPLOYMENT[trim($code)] ?? null;
	}

	public static function income(string $code): ?string
	{
		return self::INCOME[trim($code)] ?? null;
	}

	public static function tenantRole(string $code): ?string
	{
		return self::TENANT_ROLE[trim($code)] ?? null;
	}

	public static function salutation(string $label): ?string
	{
		return self::SALUTATION[trim($label)] ?? null;
	}

	/** Fallback when the source nationality carries no country ("Andere"/empty/unknown). Decided: CH. */
	public const NATIONALITY_FALLBACK = 'CH';

	/**
	 * Nationality → ISO alpha-2. Already-2-letter uppercase codes pass through;
	 * known German names translate; everything else (incl. "Andere") is null.
	 */
	public static function nationality(string $value): ?string
	{
		$v = trim($value);

		if (preg_match('/^[A-Z]{2}$/', $v)) {
			return $v;
		}

		return self::NATIONALITY_NAMES[$v] ?? null;
	}

	/** Nationality with the decided CH fallback applied — what the writer persists. */
	public static function nationalityOrFallback(string $value): string
	{
		return self::nationality($value) ?? self::NATIONALITY_FALLBACK;
	}

	/** DEBT_ENFORCEMENT_YN: 1 = yes, 0/2 = no, empty = unknown (null). */
	public static function debtEnforcement(string $code): ?bool
	{
		return match (trim($code)) {
			'1' => true,
			'0', '2' => false,
			default => null,
		};
	}

	/**
	 * Yes/No fields that appear as Ja/Nein (text gen) or 1/0 (numeric gen).
	 * Returns null for anything unrecognised so the caller can flag it.
	 */
	public static function yesNo(string $value): ?bool
	{
		return match (trim($value)) {
			'Ja', '1' => true,
			'Nein', '0' => false,
			default => null,
		};
	}

	/**
	 * RENT_PREFERENCES/NO_ELEVATOR_YN → wants_elevator. Despite the "NO_" tag the form
	 * label is "Lift" (wants a lift): 1 = yes, 2 = no, 0/empty/unknown = no answer (null).
	 */
	public static function elevator(string $code): ?bool
	{
		return match (trim($code)) {
			'1' => true,
			'2' => false,
			default => null,
		};
	}

	/** "Pers2" → 2; a bare "2" → 2; "Pers"/junk → null. */
	public static function persons(string $value): ?int
	{
		$v = trim($value);

		if (preg_match('/Pers(\d+)/', $v, $m)) {
			return (int) $m[1];
		}

		return ctype_digit($v) ? (int) $v : null;
	}

	/** The Kreise that actually exist as District enum cases. */
	public const DISTRICTS = [4, 5, 6, 7, 8, 10];

	/**
	 * District CSV → [mapped slugs, unmapped tokens]. Handles every format in the
	 * data: "Kreis4" (text gen), bare "4" (numeric gen), "Kreis 8" (spaced) and
	 * "Alle Kreise" (→ all). A number outside the real Kreise is left unmapped.
	 */
	public static function districts(string $csv): array
	{
		$mapped = [];
		$unmapped = [];

		foreach (self::tokens($csv) as $token) {
			if (str_contains(mb_strtolower($token), 'alle')) {
				foreach (self::DISTRICTS as $k) {
					$mapped[] = 'kreis_' . $k;
				}
			} elseif (preg_match('/(\d+)/', $token, $m) && in_array((int) $m[1], self::DISTRICTS, true)) {
				$mapped[] = 'kreis_' . $m[1];
			} else {
				$unmapped[] = $token;
			}
		}

		return [array_values(array_unique($mapped)), $unmapped];
	}

	/**
	 * Floor CSV → [mapped slugs, unmapped tokens]. Ground options (Stw0,
	 * "StwHochparterre", Erdgeschoss) → eg_hochparterre; anything Stw1..Stw6 or
	 * "…Stock"/"Obergeschoss" → obergeschoss, matching the floor-collapse migration.
	 * Deduped per application.
	 */
	public static function floors(string $csv): array
	{
		$mapped = [];
		$unmapped = [];

		foreach (self::tokens($csv) as $token) {
			$l = mb_strtolower($token);

			if (str_contains($l, 'alle')) {
				$mapped[] = 'eg_hochparterre';
				$mapped[] = 'obergeschoss';
			} elseif ($token === 'Stw0' || $l === 'eg' || $token === '0' || str_contains($l, 'hochparterre') || str_contains($l, 'parterre') || str_contains($l, 'erdgeschoss')) {
				$mapped[] = 'eg_hochparterre';
			} elseif (preg_match('/stw\s*[1-6]/i', $token) || preg_match('/^[1-6]$/', $token) || str_contains($l, 'stock') || str_contains($l, 'obergeschoss')) {
				$mapped[] = 'obergeschoss';
			} else {
				$unmapped[] = $token;
			}
		}

		return [array_values(array_unique($mapped)), $unmapped];
	}

	/** Room slugs derived from household size (not the legacy ROOMS_QTY). */
	public static function roomsForPersons(?int $persons): array
	{
		return $persons === null ? [] : Room::slugsForPersons($persons);
	}

	/**
	 * relationship_to_main from the free-text SUB_TENANT_TYPE / RELATIONSHIP node.
	 * Most-specific first ("eingetragene Partnerschaft" before "Lebenspartner").
	 * "Kinder" is a household fact, not a relationship, so it is ignored here.
	 */
	public static function relationship(string $text): ?string
	{
		$t = mb_strtolower($text);

		return match (true) {
			str_contains($t, 'eingetragene') => 'registered_partner',
			str_contains($t, 'ehepartner'), str_contains($t, 'ehefrau'), str_contains($t, 'ehemann'), str_contains($t, 'gatt') => 'spouse',
			str_contains($t, 'lebenspartner') => 'life_partner',
			str_contains($t, 'mitbewohner') => 'roommate',
			str_contains($t, 'partner') => 'life_partner',
			default => null,
		};
	}

	/**
	 * Birth years from the free-text "Jahrgang der Kinder". Only confident 4-digit
	 * years in 1950..2029 (so a "2034" typo is excluded, not silently accepted).
	 *
	 * @return int[]
	 */
	public static function birthYears(string $text): array
	{
		preg_match_all('/\b(19[5-9]\d|20[0-2]\d)\b/', $text, $m);

		return array_map('intval', $m[1]);
	}

	/** Split a comma-separated multiselect into trimmed, non-empty tokens. */
	private static function tokens(string $csv): array
	{
		return array_values(array_filter(array_map('trim', explode(',', $csv)), fn ($t) => $t !== ''));
	}
}
