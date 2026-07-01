<?php

namespace App\Support\Legacy;

use App\Models\Application;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;

/**
 * Builds one new application graph from a single legacy export record (decoded
 * JSON + its xml_form). Pure construction — the caller owns the transaction,
 * idempotency and the reference-number sequence. Mappings live in LegacyMaps;
 * the decisions behind them are documented in docs/legacy-import.md.
 */
class LegacyImporter
{
	private const IMPORT_REASON = 'Import aus Altsystem';

	public function __construct(private int $authorUserId)
	{
	}

	public function import(array $data): Application
	{
		$xml = simplexml_load_string((string) ($data['xml_form'] ?? ''));

		if ($xml === false) {
			throw new \RuntimeException('xml_form is not valid XML');
		}

		// The top-level JSON holds the production (current) values; the xml_form holds the
		// original submission. Where both carry a field, production wins, with the XML as
		// fallback. (For the overlapping rental_request fields they agree across the data,
		// but this keeps the importer faithful to that source-of-truth distinction.)
		$childrenCount = (int) $this->x($xml, 'ACCOMMODATION/CHILDREN_QTY');
		$persons = LegacyMaps::persons((string) data_get($data, 'rental_request.persons', ''))
			?? LegacyMaps::persons($this->x($xml, 'ACCOMMODATION/TOTAL_PERSONS'))
			?? (((int) $this->x($xml, 'ACCOMMODATION/ADULTS_QTY') + $childrenCount) ?: 1);
		$adults = (int) $this->x($xml, 'ACCOMMODATION/ADULTS_QTY') ?: max(1, $persons - $childrenCount);

		$status = LegacyMaps::status((string) ($data['status'] ?? '')) ?? 'opened';
		$openedAt = $this->date($this->x($xml, 'FORM_EROEFFNET'))
			?? $this->date($data['created_at'] ?? null)
			?? Carbon::now();
		$lastChangedAt = $this->date($data['updated_at'] ?? null) ?? $openedAt;

		$application = Application::create([
			'reference_number' => (int) $data['form_nr'],
			'status' => $status,
			'shares_apartment' => LegacyMaps::yesNo($this->x($xml, 'SUB_TENANT_YN')) ?? false,
			'wants_elevator' => LegacyMaps::elevator($this->x($xml, 'RENT_PREFERENCES/NO_ELEVATOR_YN')),
			'max_gross_rent' => $this->decimal((string) data_get($data, 'rental_request.max_rent', ''))
				?? $this->decimal($this->x($xml, 'RENT_PREFERENCES/MAX_RENT'))
				?? 0,
			'earliest_move_in' => $this->date(data_get($data, 'rental_request.from'))
				?? $this->date($this->x($xml, 'RENT_PREFERENCES/MIN_START_DATE'))
				?? $openedAt,
			'property_group' => $this->nullable($this->x($xml, 'RENT_PREFERENCES/TK_OBJGRUP'), 50),
			'property_class' => $this->nullable($this->x($xml, 'RENT_PREFERENCES/TK_OBJKLAS'), 50),
			'total_persons' => $persons,
			'adults_count' => $adults,
			'children_count' => $childrenCount,
			'all_children_live_constantly' => LegacyMaps::yesNo($this->x($xml, 'ACCOMMODATION/CHILDREN_LIVING_CONSTANTLY_QTY')),
			'has_pets' => LegacyMaps::yesNo($this->x($xml, 'ACCOMMODATION/PETS_YN')),
			'pets_description' => $this->nullable($this->x($xml, 'ACCOMMODATION/PETS'), 200),
			'remarks' => $this->nullable($this->x($xml, 'ACCOMMODATION/REMARKS')),
			'opened_at' => $openedAt,
			'last_changed_at' => $lastChangedAt,
			// FORM_VERLAENGERT sits next to FORM_EROEFFNET in the XML header; most are zero-dates
			// (→ null via date()), and the older code-2 exports omit the header block entirely.
			'extended_at' => $this->date($this->x($xml, 'FORM_VERLAENGERT')),
		]);

		$application->statusEvents()->create([
			'from_status' => null,
			'to_status' => $status,
			'occurred_at' => $openedAt,
			'reason' => self::IMPORT_REASON,
			'actor_user_id' => null,
		]);

		$this->importPreferences($application, $xml, $data, $persons);
		$this->importChildren($application, $xml, $childrenCount, $openedAt);
		$this->importApplicant($application, $data['applicant1'] ?? [], $this->child($xml, 'MAIN_TENANT'), 'main_applicant', 1, null);

		$co = $data['applicant2'] ?? [];
		if (trim(($co['firstname'] ?? '') . ($co['lastname'] ?? '')) !== '') {
			$subNode = $this->child($xml, 'SUB_TENANT');
			$relText = $this->x($subNode, 'RELATIONSHIP') ?: $this->x($xml, 'SUB_TENANT_TYPE');
			$this->importApplicant($application, $co, $subNode, 'co_applicant', 2, $relText);
		}

		$this->importNotes($application, $data['notes'] ?? [], $openedAt);

		return $application;
	}

	private function importPreferences(Application $application, SimpleXMLElement $xml, array $data, int $persons): void
	{
		// Production (top-level) districts/floors win; fall back to the submitted XML.
		$districtSource = trim((string) data_get($data, 'rental_request.district', '')) ?: $this->x($xml, 'RENT_PREFERENCES/DISTRICT_ID');
		$floorSource = trim((string) data_get($data, 'rental_request.floor', '')) ?: $this->x($xml, 'RENT_PREFERENCES/FLOOR_ID');

		[$districts] = LegacyMaps::districts($districtSource);
		[$floors] = LegacyMaps::floors($floorSource);

		$this->insertPivot('application_districts', 'district_slug', $application->id, $districts);
		$this->insertPivot('application_floors', 'floor_slug', $application->id, $floors);
		$this->insertPivot('application_rooms', 'room_slug', $application->id, LegacyMaps::roomsForPersons($persons));
	}

	private function importChildren(Application $application, SimpleXMLElement $xml, int $childrenCount, Carbon $openedAt): void
	{
		$ageText = $this->x($xml, 'ACCOMMODATION/CHILDREN_AGE_GROUP');
		$years = LegacyMaps::birthYears($ageText);

		foreach ($years as $i => $year) {
			$application->children()->create(['position' => $i + 1, 'birth_year' => $year]);
		}

		// Unparseable ages: keep the raw text as a note rather than fabricate years.
		if ($childrenCount > 0 && $years === [] && $ageText !== '') {
			$this->addNote($application, $this->mergeBody('Jahrgang der Kinder (Import)', $ageText), $openedAt);
		}
	}

	private function importApplicant(Application $application, array $json, ?SimpleXMLElement $node, string $role, int $position, ?string $relationshipText): void
	{
		[$street, $streetNumber] = LegacyMaps::splitStreet($this->x($node, 'ADDRESS/STREET'));
		[$postalCode, $city] = LegacyMaps::splitPostalCity($this->x($node, 'ADDRESS/POSTAL_CODE_CITY'));

		$applicant = $application->applicants()->create([
			'role' => $role,
			'position' => $position,
			'salutation' => LegacyMaps::salutation((string) ($json['salutation'] ?? '')) ?? 'other',
			'first_name' => mb_substr(trim((string) ($json['firstname'] ?? '')), 0, 100),
			'last_name' => mb_substr(trim((string) ($json['lastname'] ?? '')), 0, 100),
			'street' => $this->nullable($street, 200),
			'street_number' => $this->nullable($streetNumber, 20),
			'postal_code' => $this->nullable($postalCode, 10),
			'city' => $this->nullable($city, 100),
			'same_address_as_main' => $position === 1 ? null : LegacyMaps::yesNo($this->x($node, 'SUB_TENANT_SAME_ADRESS_YN')),
			'birth_date' => $this->date($this->x($node, 'BIRTHDATE')),
			'marital_status' => LegacyMaps::marital($this->x($node, 'MARITAL_STATUS')),
			'nationality' => LegacyMaps::nationalityOrFallback((string) ($json['nationality'] ?? '')),
			'place_of_origin' => $this->nullable($this->x($node, 'HOME_TOWN'), 100),
			'residence_permit' => LegacyMaps::residencePermit($this->x($node, 'RESIDENCE_PERMIT')),
			'swiss_residence_since' => $this->date($this->x($node, 'SWISS_RESIDENCE_SINCE')),
			'mobile_phone' => $this->nullable($json['phone_private'] ?? '', 50),
			'landline_phone' => $this->nullable($json['phone_business'] ?? '', 50),
			'email' => $this->nullable($json['email'] ?? '', 255),
			'occupation' => $this->nullable($json['profession'] ?? '', 200),
			'employment_status' => LegacyMaps::employment($this->x($node, 'EMPLOYMENT_SITUATION')),
			'debt_enforcement_last_2y' => LegacyMaps::debtEnforcement($this->x($node, 'DEBT_ENFORCEMENT_YN')),
			'relationship_to_main' => $relationshipText ? LegacyMaps::relationship($relationshipText) : null,
		]);

		$this->importEmployer($applicant, $node);
		$this->importCurrentHousing($applicant, $node);
	}

	private function importEmployer(\App\Models\Applicant $applicant, ?SimpleXMLElement $node): void
	{
		if (LegacyMaps::employment($this->x($node, 'EMPLOYMENT_SITUATION')) !== 'employed') {
			return;
		}

		$name = $this->x($node, 'CURRENT_EMPLOYER/NAME');
		$workload = $this->x($node, 'WORKLOAD');
		$income = LegacyMaps::income($this->x($node, 'ANNUAL_INCOME'));

		// "employer iff employed" — but only when the row is complete; otherwise skip it.
		if ($name === '' || ! ctype_digit($workload) || $income === null) {
			return;
		}

		$applicant->employer()->create([
			'name' => mb_substr($name, 0, 200),
			'workload_percent' => min(100, (int) $workload), // a stray "1000%" typo overflows the tinyint
			'annual_income_bracket_slug' => $income,
		]);
	}

	private function importCurrentHousing(\App\Models\Applicant $applicant, ?SimpleXMLElement $node): void
	{
		$tenantRole = LegacyMaps::tenantRole($this->x($node, 'CURRENT_RENT/TENANT_ROLE'));
		$landlord = $this->x($node, 'CURRENT_RENT/CURRENT_RENTER/NAME');

		if ($tenantRole === null && $landlord === '') {
			return;
		}

		$applicant->currentHousing()->create([
			'tenant_role' => $tenantRole,
			'terminated_by_landlord' => LegacyMaps::terminatedByLandlord($this->x($node, 'CURRENT_RENT/RENT_TERMINATION/TERMINATOR')),
			'termination_reason' => $this->nullable($this->x($node, 'CURRENT_RENT/RENT_TERMINATION/REASON')),
			'landlord_name' => $this->nullable($landlord, 200),
			'landlord_contact_person' => null,
			'landlord_phone' => $this->nullable($this->x($node, 'CURRENT_RENT/CURRENT_RENTER/PHONE'), 255),
		]);
	}

	private function importNotes(Application $application, array $notes, Carbon $openedAt): void
	{
		foreach ($notes as $note) {
			$this->addNote(
				$application,
				$this->mergeBody($note['title'] ?? '', $note['text'] ?? ''),
				$this->date($note['date'] ?? null) ?? $openedAt,
			);
		}
	}

	private function addNote(Application $application, ?string $body, Carbon $date): void
	{
		$note = $application->notes()->make([
			'body' => $body,
			'important' => false,
			'user_id' => $this->authorUserId,
		]);

		// Preserve the legacy note date; without disabling timestamps Eloquent stamps "now".
		$note->timestamps = false;
		$note->created_at = $date;
		$note->updated_at = $date;
		$note->save();
	}

	/** Legacy notes carry a title + text; fold them into one body, joined by a newline when both exist. */
	private function mergeBody(?string $title, ?string $text): ?string
	{
		$title = trim((string) $title);
		$text = trim((string) $text);

		if ($title !== '' && $text !== '') {
			return $title . "\n" . $text;
		}

		return ($title !== '' ? $title : null) ?? ($text !== '' ? $text : null);
	}

	private function insertPivot(string $table, string $column, int $applicationId, array $slugs): void
	{
		foreach ($slugs as $slug) {
			DB::table($table)->insert(['application_id' => $applicationId, $column => $slug]);
		}
	}

	private function child(?SimpleXMLElement $node, string $name): ?SimpleXMLElement
	{
		return $node !== null && isset($node->{$name}) ? $node->{$name} : null;
	}

	/** Trimmed text at a slash path under a SimpleXML node, or '' if absent. */
	private function x(?SimpleXMLElement $node, string $path): string
	{
		if ($node === null) {
			return '';
		}

		foreach (explode('/', $path) as $step) {
			if (! isset($node->{$step})) {
				return '';
			}
			$node = $node->{$step};
		}

		return trim((string) $node);
	}

	/** Trim, null-if-empty, and (when $max is given) clip to the column width. */
	private function nullable(string|int|null $value, ?int $max = null): ?string
	{
		$v = trim((string) $value);

		if ($v === '') {
			return null;
		}

		return $max === null ? $v : mb_substr($v, 0, $max);
	}

	private function decimal(string $value): ?float
	{
		return $value === '' ? null : (float) $value;
	}

	private function date(string|null $value): ?Carbon
	{
		$value = trim((string) $value);

		if ($value === '') {
			return null;
		}

		try {
			$date = Carbon::parse($value);
		} catch (InvalidFormatException) {
			return null;
		}

		// Reject zero-dates ("0000-00-00" → year -0001) and other nonsense.
		return $date->year >= 1900 && $date->year <= (int) Carbon::now()->year + 1 ? $date : null;
	}
}
