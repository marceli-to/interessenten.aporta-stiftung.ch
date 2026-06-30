<?php

namespace App\Console\Commands;

use App\Support\Legacy\LegacyMaps;
use Illuminate\Console\Command;

/**
 * Read-only coverage report for the legacy import (storage/import/*.json).
 * Writes nothing — it parses every file, applies the resolved mappings
 * (docs/legacy-import.md) and reports how many rows each table would receive plus
 * every value that still falls outside a mapping. Run before building the writer.
 */
class ImportLegacyDryRun extends Command
{
	protected $signature = 'app:import-legacy-dry-run
		{--dir= : Override the import directory (default storage/import)}
		{--samples=4 : Sample form numbers to show per flag}
		{--limit=0 : Process only the first N files (0 = all)}';

	protected $description = 'Dry-run coverage report for the legacy data import (no writes)';

	/** @var array<string, array{count:int, values:array<string,int>, samples:array<string,int[]>}> */
	private array $flags = [];

	private array $rows = [
		'applications' => 0,
		'applicants' => 0,
		'employers' => 0,
		'current_housings' => 0,
		'children' => 0,
		'notes' => 0,
		'status_events' => 0,
		'application_districts' => 0,
		'application_floors' => 0,
		'application_rooms' => 0,
	];

	public function handle(): int
	{
		$dir = $this->option('dir') ?: storage_path('import');
		$files = glob(rtrim($dir, '/') . '/*.json') ?: [];
		sort($files, SORT_NATURAL);

		if ($limit = (int) $this->option('limit')) {
			$files = array_slice($files, 0, $limit);
		}

		if (! $files) {
			$this->error("No JSON files found in {$dir}");

			return self::FAILURE;
		}

		$total = count($files);
		$parseErrors = 0;
		$gen = ['text' => 0, 'numeric' => 0, 'unknown' => 0];
		$mainApplicants = 0;
		$subApplicants = 0;

		$this->info("Scanning {$total} files in {$dir} …");
		$bar = $this->output->createProgressBar($total);

		foreach ($files as $file) {
			$bar->advance();
			$nr = basename($file, '.json');
			$data = json_decode((string) file_get_contents($file), true);

			if (! is_array($data)) {
				$parseErrors++;
				$this->flag('json_parse_error', $nr, $nr);

				continue;
			}

			$xml = @simplexml_load_string((string) ($data['xml_form'] ?? ''));
			if ($xml === false) {
				$parseErrors++;
				$this->flag('xml_parse_error', $nr, $nr);

				continue;
			}

			$this->rows['applications']++;
			$this->rows['status_events']++;

			// --- status ---
			$statusCode = (string) ($data['status'] ?? '');
			if (LegacyMaps::status($statusCode) === null) {
				$this->flag('status_unmapped', $statusCode === '' ? '(empty)' : $statusCode, $nr);
			}

			// --- generation ---
			$subYn = $this->x($xml, 'SUB_TENANT_YN');
			$gen[match (true) {
				in_array($subYn, ['Ja', 'Nein'], true) => 'text',
				in_array($subYn, ['0', '1'], true) => 'numeric',
				default => 'unknown',
			}]++;

			// --- household / preferences (application-level) ---
			$personsRaw = $this->x($xml, 'ACCOMMODATION/TOTAL_PERSONS');
			$persons = LegacyMaps::persons($personsRaw);
			if ($persons === null) {
				// writer fallback: adults + children, else 1
				$persons = ((int) $this->x($xml, 'ACCOMMODATION/ADULTS_QTY') + (int) $this->x($xml, 'ACCOMMODATION/CHILDREN_QTY')) ?: 1;
				$this->flag('persons_fallback', $personsRaw ?: '(empty)', $nr);
			}

			$maxRent = $this->x($xml, 'RENT_PREFERENCES/MAX_RENT') ?: (string) data_get($data, 'rental_request.max_rent', '');
			if ($maxRent === '') {
				$this->flag('missing_max_rent', $nr, $nr);
			}

			$moveIn = $this->x($xml, 'RENT_PREFERENCES/MIN_START_DATE') ?: (string) data_get($data, 'rental_request.from', '');
			if ($moveIn === '') {
				$this->flag('missing_earliest_move_in', $nr, $nr);
			}

			// wants_elevator (nullable): 1=yes, 2=no, 0/empty=null
			if (LegacyMaps::elevator($this->x($xml, 'RENT_PREFERENCES/NO_ELEVATOR_YN')) === null) {
				$this->flag('elevator_null', $this->x($xml, 'RENT_PREFERENCES/NO_ELEVATOR_YN') ?: '(empty)', $nr);
			}

			// --- districts / floors / rooms ---
			[$districts, $badDistricts] = LegacyMaps::districts($this->x($xml, 'RENT_PREFERENCES/DISTRICT_ID'));
			$this->rows['application_districts'] += count($districts);
			foreach ($badDistricts as $t) {
				$this->flag('district_token_unmapped', $t, $nr);
			}

			[$floors, $badFloors] = LegacyMaps::floors($this->x($xml, 'RENT_PREFERENCES/FLOOR_ID'));
			$this->rows['application_floors'] += count($floors);
			foreach ($badFloors as $t) {
				$this->flag('floor_token_unmapped', $t, $nr);
			}

			$this->rows['application_rooms'] += count(LegacyMaps::roomsForPersons($persons));

			// --- children ---
			$childQty = (int) $this->x($xml, 'ACCOMMODATION/CHILDREN_QTY');
			$ageText = $this->x($xml, 'ACCOMMODATION/CHILDREN_AGE_GROUP');
			$years = LegacyMaps::birthYears($ageText);
			$this->rows['children'] += count($years);
			if ($childQty > 0 && count($years) === 0) {
				if ($ageText !== '') {
					$this->rows['notes']++; // fallback import note carrying the raw age text
					$this->flag('children_raw_to_note', mb_substr($ageText, 0, 40), $nr);
				} else {
					$this->flag('children_qty_no_age_text', $nr, $nr); // count preserved, nothing to stash
				}
			} elseif ($childQty > 0 && count($years) < $childQty) {
				$this->flag('children_partial_years', "{$nr}: {$childQty} kids / " . count($years) . ' years', $nr);
			}

			// --- notes ---
			$this->rows['notes'] += count($data['notes'] ?? []);

			// --- applicants ---
			$mainApplicants++;
			$this->rows['applicants']++;
			$this->scanApplicant($nr, $data['applicant1'] ?? [], $xml->MAIN_TENANT ?? null, isSub: false);

			$a2 = $data['applicant2'] ?? [];
			$hasSub = trim((string) ($a2['firstname'] ?? '')) !== '' || trim((string) ($a2['lastname'] ?? '')) !== '';
			if ($hasSub) {
				$subApplicants++;
				$this->rows['applicants']++;
				$this->scanApplicant($nr, $a2, $xml->SUB_TENANT ?? null, isSub: true);
			}
		}

		$bar->finish();
		$this->newLine(2);

		$this->renderReport($total, $parseErrors, $gen, $mainApplicants, $subApplicants);

		return self::SUCCESS;
	}

	private function scanApplicant(string $nr, array $json, ?\SimpleXMLElement $node, bool $isSub): void
	{
		$role = $isSub ? 'sub' : 'main';

		// salutation (top-level JSON, fall back to XML)
		$sal = (string) ($json['salutation'] ?? '');
		if ($sal === '' && $node) {
			$sal = $this->x($node, 'SALUTATION');
		}
		if ($sal !== '' && LegacyMaps::salutation($sal) === null) {
			$this->flag('salutation_unmapped', $sal, $nr);
		}

		// nationality (top-level JSON) — "Andere"/empty/unknown defaults to CH (decided)
		$nat = (string) ($json['nationality'] ?? '');
		if (LegacyMaps::nationality($nat) === null) {
			$this->flag('nationality_default_ch', $nat === '' ? '(empty)' : $nat, $nr);
		}

		// applicant fields now nullable (relaxed for import) — counted as info, not a blocker
		if (trim((string) ($json['email'] ?? '')) === '') {
			$this->flag("null_email_{$role}", $nr, $nr);
		}
		if (trim((string) ($json['phone_private'] ?? '')) === '') {
			$this->flag("null_mobile_{$role}", $nr, $nr);
		}
		if (trim((string) ($json['profession'] ?? '')) === '') {
			$this->flag("null_occupation_{$role}", $nr, $nr);
		}

		if (! $node) {
			$this->flag("missing_xml_block_{$role}", $nr, $nr);

			return;
		}

		// birth date now nullable (relaxed for import)
		if ($this->x($node, 'BIRTHDATE') === '') {
			$this->flag("null_birthdate_{$role}", $nr, $nr);
		}

		// marital (nullable: code 0/empty → null)
		$mar = $this->x($node, 'MARITAL_STATUS');
		if (LegacyMaps::marital($mar) === null) {
			$this->flag('null_marital', $mar === '' ? '(empty)' : $mar, $nr);
		}

		// employment + employer (employment_status nullable: empty → null)
		$empCode = $this->x($node, 'EMPLOYMENT_SITUATION');
		$emp = LegacyMaps::employment($empCode);
		if ($emp === null) {
			$this->flag('null_employment', $empCode === '' ? '(empty)' : $empCode, $nr);
		}
		// employer row only when employed AND the row is complete (name + workload + income);
		// otherwise skip it, keeping "employer iff complete".
		if ($emp === 'employed') {
			$complete = $this->x($node, 'CURRENT_EMPLOYER/NAME') !== ''
				&& ctype_digit($this->x($node, 'WORKLOAD'))
				&& LegacyMaps::income($this->x($node, 'ANNUAL_INCOME')) !== null;
			if ($complete) {
				$this->rows['employers']++;
			} else {
				$this->flag('employer_skipped_incomplete', $nr, $nr);
			}
		}

		// current housing (only when there is a current tenancy); fields now nullable
		$roleCode = $this->x($node, 'CURRENT_RENT/TENANT_ROLE');
		$landlord = $this->x($node, 'CURRENT_RENT/CURRENT_RENTER/NAME');
		if (LegacyMaps::tenantRole($roleCode) !== null || $landlord !== '') {
			$this->rows['current_housings']++;
			if (LegacyMaps::tenantRole($roleCode) === null) {
				$this->flag('null_tenant_role', $roleCode === '' ? '(empty)' : $roleCode, $nr);
			}
			if ($landlord === '') {
				$this->flag('null_landlord_name', $nr, $nr);
			}
		}

		// relationship_to_main (sub only) — free text, nullable, just measure coverage
		if ($isSub) {
			$relText = $this->x($node, 'RELATIONSHIP');
			if ($relText === '') {
				$relText = $this->x($node->xpath('ancestor::*/SUB_TENANT_TYPE')[0] ?? null, '.');
			}
			if ($relText !== '' && LegacyMaps::relationship($relText) === null) {
				$this->flag('relationship_unmapped', mb_substr($relText, 0, 30), $nr);
			}
		}
	}

	/** Trimmed text at a slash path under a SimpleXML node, or '' if absent. */
	private function x(?\SimpleXMLElement $node, string $path): string
	{
		if (! $node) {
			return '';
		}

		if ($path === '.') {
			return trim((string) $node);
		}

		foreach (explode('/', $path) as $step) {
			$node = $node->{$step} ?? null;
			if (! $node || ! isset($node[0])) {
				return '';
			}
		}

		return trim((string) $node);
	}

	private function flag(string $category, string $value, string $sampleNr): void
	{
		$f = &$this->flags[$category];
		$f['count'] = ($f['count'] ?? 0) + 1;
		$f['values'][$value] = ($f['values'][$value] ?? 0) + 1;

		$cap = (int) $this->option('samples');
		$f['samples'][$value] ??= [];
		if (count($f['samples'][$value]) < $cap) {
			$f['samples'][$value][] = $sampleNr;
		}
	}

	private function renderReport(int $total, int $parseErrors, array $gen, int $main, int $sub): void
	{
		$this->info('=== Source ===');
		$this->line("  files: {$total}   parse errors: {$parseErrors}");
		$this->line("  form generation: text={$gen['text']}  numeric={$gen['numeric']}  unknown={$gen['unknown']}");
		$this->line("  applicants: {$main} main + {$sub} sub = " . ($main + $sub));
		$this->newLine();

		$this->info('=== Rows that would be created ===');
		$this->table(
			['table', 'rows'],
			collect($this->rows)->map(fn ($n, $t) => [$t, number_format($n)])->values()->all(),
		);

		$this->info('=== Flags (values still outside a mapping / missing required) ===');
		if (! $this->flags) {
			$this->line('  none 🎉');

			return;
		}

		uasort($this->flags, fn ($a, $b) => $b['count'] <=> $a['count']);

		foreach ($this->flags as $category => $info) {
			$this->newLine();
			$this->line("  <comment>{$category}</comment>  ({$info['count']} occurrences)");
			arsort($info['values']);
			foreach (array_slice($info['values'], 0, 12, true) as $value => $n) {
				$samples = implode(', ', $info['samples'][$value] ?? []);
				$this->line(sprintf('     %-44s %5d   e.g. %s', mb_strimwidth($value, 0, 44), $n, $samples));
			}
			if (count($info['values']) > 12) {
				$this->line('     … ' . (count($info['values']) - 12) . ' more distinct values');
			}
		}
	}
}
