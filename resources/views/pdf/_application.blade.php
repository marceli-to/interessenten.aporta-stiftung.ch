{{--
  Eine einzelne Wohnungsbewerbung als Layout-Tabelle (Deckblatt + Abschnitte).
  Erwartet $a (aufgelöste Anzeige-Daten, siehe App\Actions\Application\Pdf\Present)
  und $generatedAt (Carbon, Zeitpunkt der Erzeugung für den Seitenkopf).

  Die thead wiederholt den Seitenkopf auf jeder Druckseite; die Seitenzahlen
  liefert der Browsershot-Footer (resources/views/pdf/footer.blade.php).
--}}
<table class="layout">
  <thead>
    <tr><td class="head-cell">
      <div class="run-head">
        <div class="doc-title">Interessentendossier</div>
        <div class="when">{{ $generatedAt->format('d.m.Y H:i') }}</div>
      </div>
    </td></tr>
  </thead>
  <tbody>
    <tr><td class="body-cell">

  {{-- ===================== DECKBLATT ===================== --}}
  @php $main = $a['applicants'][0] ?? null; @endphp
  <section class="cover">
    <h1 class="title">Interessent Nr. {{ $a['reference_number'] }}</h1>

    <dl class="cover-summary">
      @if($main)
        <div class="pair"><dt>Hauptmieter</dt><dd>{{ collect([$main['salutation'], $main['name']])->filter()->join(' ') }}</dd></div>
      @endif
      <div class="pair"><dt>Eröffnet</dt><dd>{{ $a['opened_at'] }}</dd></div>
      <div class="pair"><dt>Status</dt><dd>{{ $a['status'] }}</dd></div>
      <div class="pair"><dt>Letzte Änderung</dt><dd>{{ $a['last_changed_at'] }}</dd></div>
    </dl>
  </section>

  {{-- ===================== PERSONEN =====================
       Beschriftung/Aufbau spiegeln die Detail-Panels (ApplicantPanel,
       EmployerPanel, HousingPanel) in resources/js/app/views/applications. --}}
  @foreach($a['applicants'] as $p)
    @php
      $personTitle = $p['is_main'] ? 'Hauptmieter' : 'Partner*in';
      $employerTitle = $p['is_main'] ? 'Aktueller Arbeitgeber' : 'Arbeitgeber Partner*in';
      $housingTitle = $p['is_main'] ? 'Aktuelle Wohnsituation' : 'Wohnsituation Partner*in';
    @endphp

    {{-- Personalien (vgl. ApplicantPanel) --}}
    <div class="block">
      <div class="bar">{{ $personTitle }}</div>
      <dl>
        <div class="row"><dt>Anrede</dt><x-pdf.val :value="$p['salutation']" /></div>
        <div class="row"><dt>Name</dt><x-pdf.val :value="$p['name']" /></div>
        @if(!$p['is_main'])
          <div class="row"><dt>Beziehung</dt><x-pdf.val :value="$p['relationship_to_main']" /></div>
        @endif
        <div class="row"><dt>Adresse</dt><x-pdf.val :value="$p['address']" /></div>
        <div class="row"><dt>Geburtsdatum</dt><x-pdf.val :value="$p['birth_date']" /></div>
        <div class="row"><dt>Zivilstand</dt><x-pdf.val :value="$p['marital_status']" /></div>
        <div class="row"><dt>Nationalität</dt><x-pdf.val :value="$p['nationality']" /></div>
        <div class="row"><dt>Telefon (mobil)</dt><x-pdf.val :value="$p['mobile_phone']" /></div>
        @if(!empty($p['landline_phone']))
          <div class="row"><dt>Telefon (Festnetz)</dt><x-pdf.val :value="$p['landline_phone']" /></div>
        @endif
        <div class="row"><dt>E-Mail</dt><x-pdf.val :value="$p['email']" /></div>
        <div class="row"><dt>Beruf</dt><x-pdf.val :value="$p['occupation']" /></div>
        <div class="row"><dt>Erwerbssituation</dt><x-pdf.val :value="$p['employment_status']" /></div>
        <div class="row"><dt>Betreibungen</dt><x-pdf.bool :value="$p['debt_enforcement_last_2y']" /></div>
      </dl>
    </div>

    {{-- Arbeitgeber (vgl. EmployerPanel) — nur wenn erfasst --}}
    @if(!empty($p['employer']))
      <div class="block">
        <div class="bar">{{ $employerTitle }}</div>
        <dl>
          <div class="row"><dt>Arbeitgeber</dt><x-pdf.val :value="$p['employer']['name']" /></div>
          <div class="row"><dt>Pensum</dt><x-pdf.val :value="$p['employer']['workload_percent']" /></div>
          <div class="row"><dt>Jahreseinkommen</dt><x-pdf.val :value="$p['employer']['annual_income_bracket']" /></div>
        </dl>
      </div>
    @endif

    {{-- Wohnsituation (vgl. HousingPanel) --}}
    @if(!empty($p['current_housing']))
      @php $ch = $p['current_housing']; @endphp
      <div class="block">
        <div class="bar">{{ $housingTitle }}</div>
        <dl>
          <div class="row"><dt>Rolle</dt><x-pdf.val :value="$ch['tenant_role']" /></div>
          <div class="row"><dt>Gekündigt durch Vermieter</dt><x-pdf.bool :value="$ch['terminated_by_landlord']" /></div>
          @if($ch['terminated_by_landlord'])
            <div class="row"><dt>Kündigungsgrund</dt><dd class="long">{{ $ch['termination_reason'] ?: '–' }}</dd></div>
          @endif
          <div class="row"><dt>Aktueller Vermieter</dt><x-pdf.val :value="$ch['landlord']" /></div>
        </dl>
      </div>
    @endif
  @endforeach

  {{-- ===================== MIETWUNSCH (vgl. HousingWishPanel) ===================== --}}
  @php $h = $a['housing_wish']; @endphp
  <div class="block">
    <div class="bar">Mietwunsch</div>
    <dl>
      <div class="row"><dt>Frühester Mietbeginn</dt><x-pdf.val :value="$h['earliest_move_in']" /></div>
      <div class="row"><dt>Max. Bruttomiete</dt><x-pdf.val :value="$h['max_gross_rent']" /></div>
      <div class="row"><dt>Stadtkreise</dt><x-pdf.val :value="$h['districts']" /></div>
      <div class="row"><dt>Stockwerke</dt><x-pdf.val :value="$h['floors']" /></div>
      <div class="row"><dt>Zimmer</dt><x-pdf.val :value="$h['rooms']" /></div>
      <div class="row"><dt>Lift</dt><x-pdf.bool :value="$h['wants_elevator']" /></div>
    </dl>
  </div>

  {{-- ===================== HAUSHALT & WEITERE ANGABEN (vgl. HouseholdPanel) ===================== --}}
  @php
    $hh = $a['household_info'];
    $childYears = collect($a['children'] ?? [])->pluck('birth_year')->filter()->implode(', ');
    $hasChildren = !empty($a['children']);
    $pets = $hh['has_pets'] ? ($hh['pets_description'] ?: 'Ja') : 'Keine';
  @endphp
  <div class="block">
    <div class="bar">Haushalt &amp; weitere Angaben</div>
    <dl>
      <div class="row"><dt>Personen im Haushalt</dt><dd>{{ $hh['total_persons'] }} ({{ $hh['adults_count'] }} Erwachsene, {{ $hh['children_count'] }} Kinder)</dd></div>
      @if($hasChildren)
        <div class="row"><dt>Kinder (Jahrgänge)</dt><x-pdf.val :value="$childYears ?: null" /></div>
        <div class="row"><dt>Kinder dauerhaft im Haushalt</dt><x-pdf.bool :value="$hh['all_children_live_constantly']" /></div>
      @endif
      <div class="row"><dt>Haustiere</dt><dd>{{ $pets }}</dd></div>
      <div class="row"><dt>Bemerkungen</dt><dd class="long">{{ $hh['remarks'] ?: '–' }}</dd></div>
    </dl>
  </div>

    </td></tr>
  </tbody>
</table>
