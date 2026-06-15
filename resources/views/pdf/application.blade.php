{{--
  PDF-Dossier einer einzelnen Wohnungsbewerbung (à Porta Stiftung).

  Datenform: ein assoziatives Array $a, das die Struktur von
  ApplicationDetailResource spiegelt — ABER mit bereits aufgelösten
  Anzeige-Labels (Enum->label(), formatierte Daten/Beträge), nicht mit
  rohen Slugs. So bleibt das Template frei von Formatierungs-/Enum-Logik.

  Gerendert via spatie/laravel-pdf (Browsershot / Headless Chrome),
  daher echtes HTML/CSS. Self-contained: Schriften & Styles inline,
  damit das PDF ohne Vite-/Asset-Pipeline reproduzierbar ist.
--}}
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Bewerbung Nr. {{ $a['reference_number'] }}</title>
<style>
  /* --- Schrift (Segment, eingebettet als Base64 für stabiles PDF-Rendering) --- */
  @font-face {
    font-family: 'Segment';
    font-weight: 400;
    font-style: normal;
    src: url('{{ $fonts['regular'] }}') format('woff2');
  }
  @font-face {
    font-family: 'Segment';
    font-weight: 500;
    font-style: normal;
    src: url('{{ $fonts['medium'] }}') format('woff2');
  }
  @font-face {
    font-family: 'Segment';
    font-weight: 700;
    font-style: normal;
    src: url('{{ $fonts['bold'] }}') format('woff2');
  }

  /* --- Seitenformat & Marken-Farben --- */
  :root {
    --blue: #194164;
    --gray: #474747;
    --light-gray: #cbcbcb;
    --muted: #6b7280;
    --hairline: #d8dde3;
    --row: #f4f6f9;
  }

  @page {
    size: A4;
    margin: 18mm 16mm 20mm 16mm;
  }

  * { box-sizing: border-box; }

  html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

  body {
    font-family: 'Segment', system-ui, sans-serif;
    color: var(--gray);
    font-size: 9.5pt;
    line-height: 1.45;
    margin: 0;
  }

  /* --- Kopf --- */
  .doc-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    border-bottom: 2px solid var(--blue);
    padding-bottom: 10px;
    margin-bottom: 18px;
  }
  .doc-head img { height: 46px; width: auto; }
  .doc-head .meta { text-align: right; }
  .doc-head .meta .ref {
    font-size: 15pt;
    font-weight: 700;
    color: var(--blue);
    letter-spacing: -0.01em;
  }
  .doc-head .meta .sub {
    color: var(--muted);
    font-size: 8pt;
    margin-top: 2px;
  }

  /* --- Status-Pille --- */
  .status {
    display: inline-block;
    border: 1px solid var(--blue);
    color: var(--blue);
    border-radius: 999px;
    padding: 1px 9px;
    font-size: 7.5pt;
    font-weight: 500;
    line-height: 1.6;
    vertical-align: middle;
  }

  /* --- Abschnitte --- */
  section { margin-bottom: 16px; }
  /* Pro Person zusammenhalten, nicht über Seiten zerreissen */
  .keep { page-break-inside: avoid; }

  h2 {
    font-size: 8pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--blue);
    border-bottom: 1px solid var(--hairline);
    padding-bottom: 4px;
    margin: 0 0 9px 0;
  }
  h3 {
    font-size: 10pt;
    font-weight: 700;
    color: var(--gray);
    margin: 0 0 2px 0;
  }
  .role-tag {
    font-size: 7.5pt;
    font-weight: 500;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }

  /* --- Definitions-Raster (Label/Wert) --- */
  .grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    column-gap: 26px;
    row-gap: 0;
  }
  .grid.one { grid-template-columns: 1fr; }

  dl { margin: 0; }
  .row {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 3px 0;
    border-bottom: 1px solid #eef1f4;
  }
  .row dt {
    color: var(--muted);
    font-weight: 400;
    white-space: nowrap;
  }
  .row dd {
    margin: 0;
    text-align: right;
    font-weight: 500;
    color: var(--gray);
  }
  .row dd.empty { color: var(--light-gray); font-weight: 400; }

  /* Person-Block */
  .person { margin-bottom: 6px; }
  .person + .person { margin-top: 14px; }
  .person-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 7px;
  }
  .subhead {
    font-size: 7.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--muted);
    margin: 11px 0 5px 0;
  }

  /* --- Kinder / einfache Tabelle --- */
  table.kids { width: 100%; border-collapse: collapse; }
  table.kids td, table.kids th {
    text-align: left;
    padding: 3px 0;
    border-bottom: 1px solid #eef1f4;
  }
  table.kids th { color: var(--muted); font-weight: 500; font-size: 8pt; }

  /* --- Notizen & Verlauf --- */
  .note {
    border-left: 3px solid var(--light-gray);
    padding: 2px 0 2px 10px;
    margin-bottom: 8px;
  }
  .note.important { border-left-color: var(--blue); }
  .note .body { color: var(--gray); }
  .note .meta { color: var(--muted); font-size: 7.5pt; margin-top: 2px; }

  .event {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 3px 0;
    border-bottom: 1px solid #eef1f4;
  }
  .event .when { color: var(--muted); white-space: nowrap; }

  .yes { color: var(--blue); font-weight: 500; }
  .muted { color: var(--muted); }
</style>
</head>
<body>

  {{-- ===================== KOPF ===================== --}}
  <header class="doc-head">
    <img src="{{ $logo }}" alt="à Porta Stiftung">
    <div class="meta">
      <div class="ref">Bewerbung Nr. {{ $a['reference_number'] }}</div>
      <div class="sub">
        <span class="status">{{ $a['status'] }}</span>
        &nbsp;Eröffnet {{ $a['opened_at'] }} · Stand {{ $a['last_changed_at'] }}
      </div>
    </div>
  </header>

  {{-- ===================== WOHNUNGSWUNSCH ===================== --}}
  @php $h = $a['housing_wish']; @endphp
  <section class="keep">
    <h2>Wohnungswunsch</h2>
    <div class="grid">
      <dl>
        <div class="row"><dt>Max. Bruttomiete</dt><x-pdf.val :value="$h['max_gross_rent']" /></div>
        <div class="row"><dt>Einzug ab</dt><x-pdf.val :value="$h['earliest_move_in']" /></div>
        <div class="row"><dt>Balkon gewünscht</dt><x-pdf.bool :value="$h['wants_balcony']" /></div>
        <div class="row"><dt>Lift gewünscht</dt><x-pdf.bool :value="$h['wants_elevator']" /></div>
      </dl>
      <dl>
        <div class="row"><dt>Kreise</dt><x-pdf.val :value="$h['districts']" /></div>
        <div class="row"><dt>Stockwerke</dt><x-pdf.val :value="$h['floors']" /></div>
        <div class="row"><dt>Zimmer</dt><x-pdf.val :value="$h['rooms']" /></div>
      </dl>
    </div>
  </section>

  {{-- ===================== HAUSHALT ===================== --}}
  @php $hh = $a['household_info']; @endphp
  <section class="keep">
    <h2>Haushalt</h2>
    <div class="grid">
      <dl>
        <div class="row"><dt>Personen total</dt><x-pdf.val :value="$hh['total_persons']" /></div>
        <div class="row"><dt>Erwachsene</dt><x-pdf.val :value="$hh['adults_count']" /></div>
        <div class="row"><dt>Kinder</dt><x-pdf.val :value="$hh['children_count']" /></div>
        <div class="row"><dt>Kinder ständig wohnhaft</dt><x-pdf.bool :value="$hh['all_children_live_constantly']" /></div>
        <div class="row"><dt>Wohnung wird geteilt</dt><x-pdf.bool :value="$a['shares_apartment']" /></div>
      </dl>
      <dl>
        <div class="row"><dt>Musiziert</dt><x-pdf.bool :value="$hh['plays_music']" /></div>
        <div class="row"><dt>Instrumente</dt><x-pdf.val :value="$hh['musical_instruments']" /></div>
        <div class="row"><dt>Haustiere</dt><x-pdf.bool :value="$hh['has_pets']" /></div>
        <div class="row"><dt>Haustiere (Beschr.)</dt><x-pdf.val :value="$hh['pets_description']" /></div>
      </dl>
    </div>
    @if(!empty($hh['remarks']))
      <div class="subhead">Bemerkungen</div>
      <div>{{ $hh['remarks'] }}</div>
    @endif
  </section>

  {{-- ===================== BEWERBER:INNEN ===================== --}}
  <section>
    <h2>Bewerber:innen</h2>
    @foreach($a['applicants'] as $p)
      <div class="person keep">
        <div class="person-head">
          <h3>{{ $p['name'] }}</h3>
          <span class="role-tag">{{ $p['role'] }}</span>
        </div>

        <div class="grid">
          <dl>
            <div class="row"><dt>Geburtsdatum</dt><x-pdf.val :value="$p['birth_date']" /></div>
            <div class="row"><dt>Zivilstand</dt><x-pdf.val :value="$p['marital_status']" /></div>
            <div class="row"><dt>Nationalität</dt><x-pdf.val :value="$p['nationality']" /></div>
            <div class="row"><dt>Heimatort</dt><x-pdf.val :value="$p['place_of_origin']" /></div>
            <div class="row"><dt>Bewilligung</dt><x-pdf.val :value="$p['residence_permit']" /></div>
            <div class="row"><dt>In CH seit</dt><x-pdf.val :value="$p['swiss_residence_since']" /></div>
          </dl>
          <dl>
            <div class="row"><dt>Adresse</dt><x-pdf.val :value="$p['address']" /></div>
            <div class="row"><dt>Mobile</dt><x-pdf.val :value="$p['mobile_phone']" /></div>
            <div class="row"><dt>Festnetz</dt><x-pdf.val :value="$p['landline_phone']" /></div>
            <div class="row"><dt>E-Mail</dt><x-pdf.val :value="$p['email']" /></div>
            @if(!empty($p['relationship_to_main']))
              <div class="row"><dt>Beziehung zu Hauptbew.</dt><x-pdf.val :value="$p['relationship_to_main']" /></div>
            @endif
          </dl>
        </div>

        <div class="subhead">Beruf &amp; Arbeitgeber</div>
        <div class="grid">
          <dl>
            <div class="row"><dt>Beruf / Tätigkeit</dt><x-pdf.val :value="$p['occupation']" /></div>
            <div class="row"><dt>Anstellung</dt><x-pdf.val :value="$p['employment_status']" /></div>
            <div class="row"><dt>Betreibungen (2 J.)</dt><x-pdf.bool :value="$p['debt_enforcement_last_2y']" /></div>
          </dl>
          <dl>
            <div class="row"><dt>Arbeitgeber</dt><x-pdf.val :value="$p['employer']['name'] ?? null" /></div>
            <div class="row"><dt>Pensum</dt><x-pdf.val :value="$p['employer']['workload_percent'] ?? null" /></div>
            <div class="row"><dt>Jahreseinkommen</dt><x-pdf.val :value="$p['employer']['annual_income_bracket'] ?? null" /></div>
          </dl>
        </div>

        @if(!empty($p['current_housing']))
          @php $ch = $p['current_housing']; @endphp
          <div class="subhead">Aktuelle Wohnsituation</div>
          <div class="grid">
            <dl>
              <div class="row"><dt>Rolle</dt><x-pdf.val :value="$ch['tenant_role']" /></div>
              <div class="row"><dt>Gekündigt durch Vermieter</dt><x-pdf.bool :value="$ch['terminated_by_landlord']" /></div>
              <div class="row"><dt>Mietdauer</dt><x-pdf.val :value="$ch['rent_duration']" /></div>
            </dl>
            <dl>
              <div class="row"><dt>Vermieter</dt><x-pdf.val :value="$ch['landlord_name']" /></div>
              <div class="row"><dt>Kontaktperson</dt><x-pdf.val :value="$ch['landlord_contact_person']" /></div>
              <div class="row"><dt>Telefon</dt><x-pdf.val :value="$ch['landlord_phone']" /></div>
            </dl>
          </div>
          @if(!empty($ch['termination_reason']))
            <div class="subhead">Kündigungsgrund</div>
            <div>{{ $ch['termination_reason'] }}</div>
          @endif
        @endif
      </div>
    @endforeach
  </section>

  {{-- ===================== KINDER ===================== --}}
  @if(!empty($a['children']))
    <section class="keep">
      <h2>Kinder</h2>
      <table class="kids">
        <thead><tr><th style="width:60px">Nr.</th><th>Jahrgang</th></tr></thead>
        <tbody>
          @foreach($a['children'] as $i => $child)
            <tr><td>{{ $i + 1 }}</td><td>{{ $child['birth_year'] }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </section>
  @endif

  {{-- ===================== NOTIZEN ===================== --}}
  @if(!empty($a['notes']))
    <section class="keep">
      <h2>Notizen</h2>
      @foreach($a['notes'] as $note)
        <div class="note {{ !empty($note['important']) ? 'important' : '' }}">
          <div class="body">{{ $note['body'] }}</div>
          <div class="meta">{{ $note['author'] }} · {{ $note['created_at'] }}</div>
        </div>
      @endforeach
    </section>
  @endif

  {{-- ===================== STATUS-VERLAUF ===================== --}}
  @if(!empty($a['status_events']))
    <section class="keep">
      <h2>Status-Verlauf</h2>
      <dl>
        @foreach($a['status_events'] as $ev)
          <div class="event">
            <span>{{ $ev['from'] ? $ev['from'].' → ' : '' }}<strong>{{ $ev['to'] }}</strong> <span class="muted">· {{ $ev['actor'] }}</span></span>
            <span class="when">{{ $ev['occurred_at'] }}</span>
          </div>
        @endforeach
      </dl>
    </section>
  @endif

</body>
</html>
