# Todo – Interessenten Aporta Stiftung

Abgeleitet aus dem Kundeninput (`.install/instructions.md`). Verweise auf den aktuellen
Code-Stand sind in Klammern angegeben, wo relevant.

Stack: Laravel 13 API + Vue 3 SPA (vue-router, pinia). Hauptansichten unter
`resources/js/app/views/applications/` (`Index.vue`, `Show.vue`, `Filter.vue`, `panels/`).

---

## 1. Anpassungen Filter & Suche

- [x] **Suche erweitern: auch Notizen + Personenanzahl durchsuchen**
      Erledigt in `app/Actions/Application/Get.php` (`applySearch`): numerische Suche
      matcht jetzt zusätzlich `total_persons`, und es wird über die `notes`-Relation
      im `body` gesucht (zusätzlich zu Referenznummer + Name/Ort des Hauptbewerbers).

- [x] **Kein Default-Filter**
      Erledigt: Das `onMounted`-Default (`status=opened` bei leerem Query) in
      `Filter.vue` entfernt. Frische Landung zeigt jetzt alle Bewerbungen (677 statt
      666). `useListQuery` + `GetApplications` filtern ohnehin nur auf gesetzte Werte.

- [x] **Statusfilter kombinieren (Mehrfachauswahl)**
      Erledigt. Status ist jetzt eine Mehrfachauswahl (analog zu Stadtkreis/Zimmer):
      - `Filter.vue`: `statusFilter` als `array`, `toggleStatus` fügt hinzu/entfernt,
        `:active` via `.includes()`. URL-Encoding kommagetrennt über `filterRef`.
      - `ApplicationController`: `status` als kommagetrennter String validiert und über
        neuen `splitStatuses()`-Helper auf gültige Enum-Werte gefiltert (bogus Werte
        werden verworfen).
      - `Get.php`: `whereIn('status', $statuses)` statt `where`.

- [x] **Filter Einkommen (Brackets)**
      Erledigt. Einkommen ist als Bracket (`IncomeBracket`-Enum, 12 geordnete
      Stufen) am Arbeitgeber des Bewerbers gespeichert. Filter als Von–Bis-Bereich
      über zwei Dropdowns, gematcht auf **nur den Hauptmieter** (Entscheid Kunde):
      - `IncomeBracket::slugsInRange()` löst die Von/Bis-Slugs über `sortOrder()`
        in die konkrete Slug-Menge auf.
      - `GetRequest`: `income_min`/`income_max` → `incomeBracketSlugs()`.
      - `Get.php`: `whereHas('mainApplicant.employer', whereIn(...))`.
      - `Filter.vue`: zwei `Select` (von/bis) aus `lookups.options('income_brackets')`.
      - Tests: Bereich, offene Grenze, „nur Hauptmieter" (Mitmieter ignoriert).

## 2. Darstellung

- [x] **Telefonnummer mit Abständen formatieren**
      Erledigt. Neuer `fmtPhone()` in `utils/format.js`: gruppiert in E.164
      gespeicherte CH-Nummern zu `+41 79 409 49 27`; Nicht-CH/unparsbare Werte
      werden unverändert angezeigt. Eingesetzt in der Anzeige von `ApplicantPanel`
      (mobile_phone) und `HousingPanel` (landlord_phone). Edit-Felder bleiben auf
      dem rohen Wert (Backend-Normalizer macht beim Speichern wieder E.164).

## 3. Listenansicht & Multiedit

Aufgeteilt in zwei Teile (Entscheid): Teil A = Bulk-Bar + In-Place-Aktionen,
Teil B = Resultatansicht/Browse als Folge-Task. Die Auswahl (angehakte Zeilen)
ist die gemeinsame Basis für alle vier Aktionen (Abwählen, Export, Löschen,
Öffnen).

**Auswahl-Modell (Entscheid):** Sammelaktionen sind **filter-gebunden** — es
gibt kein globales „alle 677 auswählen". Ablauf:
- Bulk-Bar erscheint **nur wenn ein Filter ODER eine Suche aktiv ist**
  (sonst ausgeblendet, ggf. Hinweis „Filter setzen").
- Checkboxen sind **Opt-in innerhalb des Filters**: Bar zeigt „5 von 213
  ausgewählt".
- Auswahl **bleibt über Seitenwechsel bestehen** (5 von 213 geht über Seiten),
  wird aber **bei Filter-/Suchwechsel geleert** (Scope geändert).
- „Alle auswählen" = **select-all-matching**: keine 213 IDs zum Client; ein
  Flag trägt den aktuellen Filter. Backend-Endpoints akzeptieren
  `{ ids }` ODER `{ filters, exclude: [ids] }`, serverseitig aufgelöst
  (Filter-Parsing aus `GetRequest`/`Get.php` wiederverwenden).

### Teil A – Bulk-Bar + Aktionen

- [ ] **Listenansicht mit Checkbox-Multiedit erweitern**
      In `Index.vue` Auswahl-Checkboxen pro Zeile + „Alle auswählen" ergänzt
      (`RowCheckbox`), schwebende `BulkActionBar` (Abwählen/Export/Löschen/Öffnen).
  - [ ] Auswahl seitenübergreifend halten; bei Filter-/Suchwechsel leeren.
  - [ ] Bar nur bei aktivem Filter/Suche zeigen; Zähler „X von N".
  - [ ] „Alle N auswählen" (select-all-matching) inkl. Ausnahmen (`exclude`).
  - [x] **Aktion: Alle (ausgewählten) löschen**
        Erledigt. `POST applications/bulk-delete` (`ApplicationBulkController`),
        Payload `{ ids }` ODER Filter-Parameter (+`exclude`) für all-matching.
        `Application\BulkDelete` löst die IDs über das geteilte
        `BuildsApplicationListQuery`-Trait auf (gleiche Logik wie die Liste),
        löscht je Zeile via `Application\Delete` (Soft-Delete, Aktivitätslog).
        Filter-Parsing in Request-Trait `ParsesApplicationFilters` (geteilt mit
        `GetRequest`). Request lehnt „weder ids noch Filter" ab (422). Frontend:
        ConfirmDialog mit echter Anzahl → API → Auswahl leeren → Liste neu laden.
        Tests: `BulkDeleteEndpointTest` (ids, all-matching, exclude, Guard, schon
        gelöscht, Auth).
  - [x] **Aktion: Alle (ausgewählten) wiederherstellen**
        Erledigt. `POST applications/bulk-restore` (gleicher Controller). Nur in
        der „Gelöscht"-Ansicht (`status=deleted`): die Bar tauscht Löschen gegen
        Wiederherstellen (nicht-destruktiv, kein ConfirmDialog). `Application\
        BulkRestore` spiegelt BulkDelete; IDs-Pfad scoped `onlyTrashed()`, Filter-
        Pfad über `trashed` im geteilten Query-Trait. Shared Request-Basis
        `BulkSelectionRequest` (Delete/Restore erben nur die Fehlermeldung).
        Tests: `BulkRestoreEndpointTest` (ids, all-matching, exclude, Guard, nicht-
        gelöscht übersprungen, Auth).
  - [ ] **Aktion: Alle (ausgewählten) exportieren** (hängt an Export-Klärung, §4;
        kommt als weitere Methode in `ApplicationBulkController`)

### Teil B – Resultatansicht / Browse (Folge-Task)

- [x] **Resultatansicht: angehakte Bewerbungen durchblättern**
      Erledigt. „Öffnen" in der Bulk-Bar löst die Auswahl serverseitig in die
      **geordnete ID-Liste** auf (`POST applications/bulk-resolve` →
      `Application\ResolveIds`, gleiche Reihenfolge wie die Liste über
      `applyListOrder` im Query-Trait; ungescopt = leer), öffnet die erste
      Bewerbung im Detail.
  - [x] Auswahl bleibt über die Navigation bestehen — **Pinia-`browse`-Store**
        (`start/clear/position/prevId/nextId`, hält nur IDs, In-Session). Einzel-
        Öffnen einer Zeile leert den Browse-Set.
  - [x] Prev/Next-UI in `Show.vue` zentriert im Header (geteilter
        `pagination/Button`, Position „3 / 12", Grenzen via disabled). `Show.vue`
        lädt bei `:id`-Wechsel neu (watch, da Komponente wiederverwendet wird).
  - [ ] PDF-Export der Resultate (hängt an PDF-Klärung, §4 — separat).
        Tests: `BulkResolveEndpointTest` (Reihenfolge, all-matching, exclude,
        trashed, leere Auswahl, Auth).

## 4. Export

- [ ] **PDF-Export (kompletter Datensatz, 1..n Bewerbungen) — ASYNCHRON**
      Den vollständigen Datensatz **einer oder mehrerer** Bewerbungen als PDF
      exportieren. Ansatz bestätigt (siehe `.install/pdf-generation.md`):
      **Spatie Laravel PDF** (Blade → HTML → Chrome) mit **Browsershot lokal**
      und **AWS Lambda via Hammerstone Sidecar** in Produktion. Ein Export-Lauf
      erzeugt **ein PDF** (alle ausgewählten Bewerbungen hintereinander, je
      Bewerbung eine neue Seite via `page-break`).

      **Async-Entscheid (Kunde):** Generierung läuft in einem **Queue-Job**, da
      eine All-Matching-Auswahl hunderte Datensätze umfassen kann (Request-/
      Lambda-Timeout vermeiden). Ablauf:
      1. Frontend startet Export → Endpoint legt einen Export-Auftrag an,
         dispatcht den Job, gibt **sofort eine `export_id`** zurück (kein PDF).
      2. Frontend **pollt** einen Status-Endpoint (`pending` → `ready` /
         `failed`).
      3. Bei `ready`: **In-App-Benachrichtigung/Toast** mit **Download-Link**
         (signierte, zeitlich befristete URL). Kein E-Mail, kein Broadcasting.
      4. PDF liegt als **temporäre Datei** (S3 in Prod / local in Dev), wird per
         **Scheduled Command nach ~24h** aufgeräumt.

      Auswahl-Auflösung ist bereits vorhanden und wird wiederverwendet: gleicher
      `{ ids }` ODER `{ filters, exclude }`-Mechanismus wie Bulk-Löschen
      (`BuildsApplicationListQuery` + `ParsesApplicationFilters`). Datenumfang =
      voller Detail-Datensatz wie im `Show`-Action-Eager-Load (Haupt-/Mitmieter
      inkl. Arbeitgeber + aktuelle Wohnsituation, Kinder, Wohnungswunsch,
      Haushalt, Notizen, Status-Verlauf).

  - [ ] **Setup: Spatie Laravel PDF + Browsershot/Sidecar**
        `composer require spatie/laravel-pdf hammerstone/sidecar
        wnx/sidecar-browsershot`; `npm install --save-dev puppeteer` (lokal).
        `config/sidecar.php` + `config/sidecar-browsershot.php` anlegen, Env-Vars
        (§6 der Doku) ergänzen. Lambda-Deploy (`sidecar:configure` +
        `sidecar:deploy --activate`) ist ein **Prod-/AWS-Schritt** — lokal ohne
        `->onLambda()` mit Puppeteer testen. Queue-Worker braucht in Prod
        AWS-Zugang (`onLambda()` läuft auf dem Worker, vgl. Doku §4/§10).
  - [ ] **Export-Auftrag-Tracking** (leichtgewichtig, kein Verlaufs-Feature)
        Status eines Laufs (`pending`/`ready`/`failed`, Pfad, `user_id`,
        `expires_at`) persistieren — Tabelle `application_exports` oder per
        Cache-Eintrag mit TTL. Genügt für Polling + signierten Download; bewusst
        **keine** durchsuchbare Export-Historie.
  - [ ] **Queue-Job `GenerateApplicationsPdfJob`**
        Auflösung der IDs (geteiltes Trait) → lädt Bewerbungen mit dem
        Eager-Load-Baum aus `Show` → rendert `Pdf::view('pdf.applications', …)`
        (`->onLambda()` nur in Prod) → speichert temporäre Datei → setzt Status
        auf `ready` (bzw. `failed` bei Exception). `tries`/`backoff` setzen.
        Reine Render-Logik ggf. in Action `GenerateApplicationsPdf` auslagern.
  - [ ] **Blade-View `pdf/applications.blade.php`** (+ ggf. Header/Footer-Partials)
        nach den Konventionen der Doku §7: eigenständiges HTML-Dokument,
        Schriften/Logo als base64 eingebettet, **inline/kompiliertes CSS** statt
        Tailwind-CDN, `page-break-before` pro Bewerbung, Seitenzahlen via
        `@pageNumber / @totalPages`. Layout an den Detail-Panels orientieren
        (alle Sektionen aus `Show.vue`).
  - [ ] **Endpoints** in `ApplicationBulkController` (neben `destroy`/`restore`):
    - [ ] `POST applications/bulk-export` (`ExportRequest extends
          BulkSelectionRequest`) — IDs auflösen, Auftrag anlegen, Job
          dispatchen, `{ export_id }` (202) zurückgeben.
    - [ ] `GET applications/exports/{export}` — Status-Polling
          (`{ status, download_url? }`), nur eigener Auftrag (Authorization).
    - [ ] `GET applications/exports/{export}/download` — signierter/temporärer
          Download der fertigen PDF; nach Ablauf 404/410.
          Routen in `routes/api.php` ergänzen.
  - [ ] **Cleanup-Command** (Scheduled): abgelaufene Export-Dateien + Tracking-
        Einträge nach ~24h entfernen (`expires_at`); in den Scheduler hängen
        (vgl. §6 Lifecycle).
  - [ ] **Frontend** (UI-Kern des Async-Flows):
    - [ ] **App-weiter Export-Tracker (Pinia-Store), NICHT an `Index.vue`
          gebunden** (Entscheid): Laufende Exporte werden global verwaltet, das
          Polling läuft **unabhängig von der aktuellen Ansicht** weiter. Damit
          erscheint die „PDF bereit"-Benachrichtigung auch dann, wenn der Nutzer
          **die Ansicht gewechselt** oder **den Filter geändert** hat (was die
          Zeilenauswahl leert) — der Export ist von der Auswahl entkoppelt,
          sobald er gestartet wurde. Mehrere parallele Exporte möglich.
          (Optional: laufende `export_id`s in `localStorage` persistieren, damit
          ein Reload den Lauf weiter pollt.)
    - [ ] `bulkExport()` in `Index.vue` verdrahten (ersetzt Konsolen-
          Platzhalter): startet Export → übergibt `export_id` an den Store
          (Store übernimmt Polling), Auswahl darf danach geleert werden.
    - [ ] **Poll-Mechanik** im Store (z.B. `useExportsStore`/`useExportPolling`):
          pollt Status-Endpoint im Intervall, stoppt je Lauf bei
          `ready`/`failed`, mit Timeout-/Fehlerbehandlung.
    - [ ] **UI-Feedback (global gemountet, z.B. im App-Layout)**: kurzer
          Ladehinweis „PDF wird erstellt …" beim Start; bei `ready`
          Toast/Benachrichtigung mit Download-Link; bei `failed` Fehlermeldung +
          Retry. (Bestehende Toast-/Notification-Komponente prüfen/
          wiederverwenden.)
    - [ ] API-Methoden in `api/applications.js`: `startExport(payload)`,
          `exportStatus(id)`, Download via signierter URL.
  - [ ] **Tests**:
    - [ ] Backend: Start-Endpoint (ids, all-matching, exclude, Guard „weder ids
          noch Filter", Auth) → dispatcht Job; Status-Endpoint (`pending`→
          `ready`, fremder Auftrag = 403); Download (ok / abgelaufen).
          Job-Test mit `Queue::fake()` bzw. synchroner Ausführung; Rendering
          lokal ohne Lambda. Grenzfälle: nur Hauptmieter, ohne Mitmieter, ohne
          Notizen/Kinder.
    - [ ] Cleanup-Command entfernt nur abgelaufene Einträge/Dateien.

      **OFFEN/Klärung Kunde:** PDF-Layout/Branding (Logo, Schrift, Reihenfolge
      der Felder).

- [ ] **Excel-Export (Datensätze)**
      Mehrere Datensätze als Excel exportieren.
  - [ ] **OFFEN/Klärung: Welche Felder sollen exportiert werden?**

## 5. Copy to Clipboard

- [ ] **Felder in die Zwischenablage kopieren**
  - [ ] **OFFEN/Klärung: Welche Felder werden kopiert?** (Definition durch Kunde)

## 6. Automatisierung / Lifecycle

- [ ] **Automatisches Archivieren**
      Regel: Anmeldung + 6 Monate + 3 Monate Kulanz → automatisch archivieren.
      (Scheduled Command + Status-Event; Status-Logik vgl. `StatusEvent` / `StatusPanel`.)

- [ ] **Automatisches Löschen**
      Regel definieren (Zeitpunkt nach Archivierung?) und als Scheduled Command umsetzen.
      → Klären: genaue Frist fürs Löschen.

## 7. Zimmer-/Wohnungsgrösse

- [ ] **Zimmerzuteilung: Anzahl Personen + 1**
      Zimmeranzahl wird aus Personenanzahl + 1 abgeleitet/zugeteilt.
  - [ ] Muss **vor der Datenübergabe** erfolgen.
  - [ ] **1/2-Zimmer-Schritte fallen weg** (nur ganze Zimmer).

## 8. Design

- [ ] **Logogrösse prüfen** (ggf. anpassen)
- [ ] **Rot anpassen** auf den Jam'on-Farbwert (CSS-Variable / Tailwind-Farbe;
      vgl. `text-red` in `Index.vue`)
- [ ] **Benutzer-Formular: Button anpassen** (`views/users/`)

---

## Offene Punkte / Rückfragen an Kunde

- Excel-Export: Welche Felder?
- Copy to Clipboard: Welche Felder?
- Automatisches Löschen: Genaue Frist?
- PDF-Ansatz/Library bestätigen.
