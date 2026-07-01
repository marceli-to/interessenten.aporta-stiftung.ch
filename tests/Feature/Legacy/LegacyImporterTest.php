<?php

use App\Models\Application;
use App\Models\User;
use App\Support\Legacy\LegacyImporter;
use Illuminate\Support\Facades\DB;

function legacyXml(array $overrides = []): string
{
	$o = array_merge([
		'main_marital' => '2',
		'main_employment' => '1',
		'no_elevator' => '2',
		'terminator' => '1',
		'swiss_since' => '0000-00-00',
		'children_qty' => '0',
		'children_age' => '',
		'verlaengert' => '2023-02-17T08:17:00',
	], $overrides);

	return <<<XML
<?xml version="1.0"?><InterestRequest>
  <FORM_EROEFFNET>2022-03-31T22:14:47</FORM_EROEFFNET>
  <FORM_VERLAENGERT>{$o['verlaengert']}</FORM_VERLAENGERT>
  <MAIN_TENANT>
    <SALUTATION>Herr</SALUTATION><LAST_NAME>Kurmann</LAST_NAME><FIRST_NAME>Stephan</FIRST_NAME>
    <ADDRESS><STREET>Herdernstrasse 74</STREET><POSTAL_CODE_CITY>8004 Zürich</POSTAL_CODE_CITY></ADDRESS>
    <BIRTHDATE>1986-07-23</BIRTHDATE><MARITAL_STATUS>{$o['main_marital']}</MARITAL_STATUS>
    <NATIONALITY>CH</NATIONALITY><HOME_TOWN>Luzern</HOME_TOWN><RESIDENCE_PERMIT/>
    <SWISS_RESIDENCE_SINCE>{$o['swiss_since']}</SWISS_RESIDENCE_SINCE>
    <EMPLOYMENT_SITUATION>{$o['main_employment']}</EMPLOYMENT_SITUATION><DEBT_ENFORCEMENT_YN>2</DEBT_ENFORCEMENT_YN>
    <CURRENT_EMPLOYER><NAME>Digitec</NAME></CURRENT_EMPLOYER><WORKLOAD>100</WORKLOAD><ANNUAL_INCOME>10</ANNUAL_INCOME>
    <CURRENT_RENT>
      <TENANT_ROLE>1</TENANT_ROLE>
      <RENT_TERMINATION><TERMINATOR>{$o['terminator']}</TERMINATOR><REASON>Sanierung</REASON></RENT_TERMINATION>
      <CURRENT_RENTER><NAME>Schaeppi</NAME><PHONE>044</PHONE></CURRENT_RENTER>
    </CURRENT_RENT>
  </MAIN_TENANT>
  <SUB_TENANT_YN>Ja</SUB_TENANT_YN><SUB_TENANT_TYPE>Lebenspartner</SUB_TENANT_TYPE>
  <SUB_TENANT>
    <SALUTATION>Herr</SALUTATION><LAST_NAME>Israeli</LAST_NAME><FIRST_NAME>Tomer</FIRST_NAME>
    <SUB_TENANT_SAME_ADRESS_YN>Ja</SUB_TENANT_SAME_ADRESS_YN>
    <ADDRESS><STREET/><POSTAL_CODE_CITY/></ADDRESS>
    <BIRTHDATE>1984-05-29</BIRTHDATE><MARITAL_STATUS>1</MARITAL_STATUS><NATIONALITY/>
    <EMPLOYMENT_SITUATION>2</EMPLOYMENT_SITUATION><DEBT_ENFORCEMENT_YN/>
  </SUB_TENANT>
  <RENT_PREFERENCES>
    <DISTRICT_ID>Kreis4, Kreis5, Kreis6</DISTRICT_ID><FLOOR_ID>Stw0, Stw1, Stw2</FLOOR_ID>
    <NO_ELEVATOR_YN>{$o['no_elevator']}</NO_ELEVATOR_YN><MAX_RENT>3000</MAX_RENT>
    <MIN_START_DATE>2022-05-01</MIN_START_DATE><TK_OBJGRUP>HNF1.1</TK_OBJGRUP><TK_OBJKLAS/>
  </RENT_PREFERENCES>
  <ACCOMMODATION>
    <TOTAL_PERSONS>Pers2</TOTAL_PERSONS><ADULTS_QTY>2</ADULTS_QTY>
    <CHILDREN_QTY>{$o['children_qty']}</CHILDREN_QTY><CHILDREN_AGE_GROUP>{$o['children_age']}</CHILDREN_AGE_GROUP>
    <PETS_YN>0</PETS_YN><PETS/><REMARKS>Hallo</REMARKS>
  </ACCOMMODATION>
</InterestRequest>
XML;
}

function legacyRecord(array $xmlOverrides = [], array $recordOverrides = []): array
{
	return array_merge([
		'form_nr' => 10,
		'status' => '2',
		'created_at' => '2022-04-11T16:12:57',
		'updated_at' => '2024-02-26T14:44:07',
		'applicant1' => ['salutation' => 'Herr', 'firstname' => 'Stephan', 'lastname' => 'Kurmann', 'email' => 's@x.ch', 'phone_private' => '+417', 'phone_business' => null, 'profession' => 'Mediensprecher', 'nationality' => 'CH'],
		'applicant2' => ['salutation' => 'Herr', 'firstname' => 'Tomer', 'lastname' => 'Israeli', 'email' => 't@x.ch', 'phone_private' => '+972', 'phone_business' => null, 'profession' => 'Sales', 'nationality' => 'Andere'],
		'rental_request' => ['from' => '2022-05-01', 'max_rent' => 3000],
		'notes' => [['id' => 1, 'date' => '2025-09-25T00:00:00', 'title' => 'Verlängerung', 'text' => null]],
		'xml_form' => legacyXml($xmlOverrides),
	], $recordOverrides);
}

beforeEach(function () {
	$this->author = User::factory()->create(['firstname' => 'Laura', 'name' => 'Cerny']);
	$this->importer = new LegacyImporter($this->author->id);
});

it('imports the full application graph with resolved mappings', function () {
	$app = $this->importer->import(legacyRecord())->fresh(['applicants.employer', 'applicants.currentHousing', 'statusEvents']);

	expect($app->reference_number)->toBe(10);
	expect($app->status->value)->toBe('extended');           // code 2
	expect($app->wants_elevator)->toBeFalse();               // NO_ELEVATOR_YN = 2
	expect($app->shares_apartment)->toBeTrue();
	expect($app->property_group)->toBe('HNF1.1');
	expect($app->opened_at->format('Y-m-d'))->toBe('2022-03-31'); // FORM_EROEFFNET
	expect($app->extended_at->format('Y-m-d'))->toBe('2023-02-17'); // FORM_VERLAENGERT
	expect($app->applicants)->toHaveCount(2);
	expect($app->statusEvents)->toHaveCount(1);
	expect($app->statusEvents->first()->to_status->value)->toBe('extended');

	$main = $app->applicants->firstWhere('role', 'main_applicant');
	expect($main->street)->toBe('Herdernstrasse');
	expect($main->street_number)->toBe('74');
	expect($main->postal_code)->toBe('8004');
	expect($main->city)->toBe('Zürich');
	expect($main->marital_status->value)->toBe('married');   // code 2
	expect($main->swiss_residence_since)->toBeNull();        // 0000-00-00 rejected
	expect($main->employer->name)->toBe('Digitec');
	expect($main->employer->annual_income_bracket_slug->value)->toBe('100k_120k'); // code 10
	expect($main->currentHousing->terminated_by_landlord)->toBeTrue();             // TERMINATOR 1
	expect($main->currentHousing->landlord_name)->toBe('Schaeppi');

	$co = $app->applicants->firstWhere('role', 'co_applicant');
	expect($co->nationality->value)->toBe('CH');             // "Andere" → CH
	expect($co->same_address_as_main)->toBeTrue();
	expect($co->employment_status->value)->toBe('student');
	expect($co->employer)->toBeNull();                       // not employed → no employer
	expect($co->relationship_to_main->value)->toBe('life_partner');

	expect(DB::table('application_districts')->where('application_id', $app->id)->count())->toBe(3);
	expect(DB::table('application_floors')->where('application_id', $app->id)->count())->toBe(2);  // Stw0→eg, Stw1/2→obergeschoss
	expect(DB::table('application_rooms')->where('application_id', $app->id)->count())->toBe(2);   // persons 2 → rooms 2,3
});

it('leaves extended_at null when FORM_VERLAENGERT is a zero-date', function () {
	$app = $this->importer->import(legacyRecord(['verlaengert' => '0000-00-00']));

	expect($app->extended_at)->toBeNull();
});

it('attributes imported notes to the given author and preserves their date', function () {
	$app = $this->importer->import(legacyRecord())->fresh('notes');

	expect($app->notes)->toHaveCount(1);
	$note = $app->notes->first();
	expect($note->body)->toBe('Verlängerung'); // title-only legacy note → title becomes the body
	expect($note->user_id)->toBe($this->author->id);
	expect($note->created_at->format('Y-m-d'))->toBe('2025-09-25');
});

it('folds legacy note title and text into one body joined by a newline', function () {
	$record = legacyRecord([], ['notes' => [
		['id' => 1, 'date' => '2025-09-25T00:00:00', 'title' => 'Wohnungsangebot', 'text' => 'Abgelehnt, zu teuer.'],
		['id' => 2, 'date' => '2024-01-10T00:00:00', 'title' => 'Verlängerung', 'text' => null],
	]]);

	$app = $this->importer->import($record)->fresh('notes');

	expect($app->notes->pluck('body')->all())->toContain("Wohnungsangebot\nAbgelehnt, zu teuer."); // both → newline-joined
	expect($app->notes->pluck('body')->all())->toContain('Verlängerung');                          // title only → title
});

it('parses 4-digit child birth years into rows', function () {
	$app = $this->importer->import(legacyRecord(['children_qty' => '1', 'children_age' => 'Kind 1: 2015']))->fresh('children');

	expect($app->children)->toHaveCount(1);
	expect($app->children->first()->birth_year)->toBe(2015);
});

it('keeps unparseable child ages as an import note instead of fabricating years', function () {
	$app = $this->importer->import(legacyRecord(['children_qty' => '1', 'children_age' => '8j. + 4j.']))->fresh('notes');

	expect($app->children)->toHaveCount(0);
	expect($app->children_count)->toBe(1); // household size still recorded
	$note = $app->notes->first(fn ($n) => str_contains((string) $n->body, 'Jahrgang der Kinder'));
	expect($note)->not->toBeNull();
	expect($note->body)->toBe("Jahrgang der Kinder (Import)\n8j. + 4j.");
});

it('prefers the production rental_request over the submitted XML where both exist', function () {
	// XML carries the original submission (max_rent 3000, Pers2, Kreis 4/5/6);
	// the top-level JSON carries the current production values and must win.
	$record = legacyRecord([], [
		'rental_request' => ['from' => '2023-01-01', 'max_rent' => 1800, 'persons' => 'Pers3', 'district' => 'Kreis7'],
	]);

	$app = $this->importer->import($record)->fresh();

	expect((float) $app->max_gross_rent)->toBe(1800.0);
	expect($app->total_persons)->toBe(3);
	expect($app->earliest_move_in->format('Y-m-d'))->toBe('2023-01-01');
	expect(DB::table('application_districts')->where('application_id', $app->id)->pluck('district_slug')->all())->toBe(['kreis_7']);
});

it('skips applications that already exist (idempotent on reference_number)', function () {
	$this->importer->import(legacyRecord());

	expect(fn () => $this->importer->import(legacyRecord()))->toThrow(Illuminate\Database\QueryException::class);
	expect(Application::where('reference_number', 10)->count())->toBe(1);
});
