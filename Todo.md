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
Teil B = Resultatansicht/Browse. Die Auswahl (angehakte Zeilen) ist die
gemeinsame Basis für die Bulk-Aktionen (Abwählen, Öffnen, Export, Löschen bzw.
Wiederherstellen in der „Gelöscht"-Ansicht).

**Stand:** Bulk-Bar, Auswahl-Modell, Löschen, Wiederherstellen und
Resultatansicht/Browse erledigt. Offen nur noch **Export** (§4).

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

- [x] **Listenansicht mit Checkbox-Multiedit erweitern**
      Erledigt. In `Index.vue` Auswahl-Checkboxen pro Zeile + „Seite auswählen"
      (`RowCheckbox`), schwebende `BulkActionBar` (Abwählen/Öffnen/Export/Löschen,
      kanonische `Button`-Komponente mit On-Dark-Varianten).
  - [x] Auswahl seitenübergreifend halten; bei Filter-/Suchwechsel leeren.
  - [x] Bar (und Checkbox-Spalte) nur bei aktivem Filter/Suche zeigen; Zähler
        „X von N".
  - [x] „Alle N auswählen" (select-all-matching) inkl. Ausnahmen (`exclude`).
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
  - [x] **Aktion: Alle (ausgewählten) exportieren**
        Erledigt (synchroner Download, siehe §4). `POST applications/bulk-export`
        (`ApplicationBulkController@export`) löst die Auswahl wie die anderen
        Bulk-Aktionen auf, rendert das PDF und streamt es zurück; Frontend
        `bulkExport()` löst den Browser-Download aus.

### Teil B – Resultatansicht / Browse (Folge-Task)

- [x] **Resultatansicht: angehakte Bewerbungen durchblättern**
      Erledigt. „Öffnen" in der Bulk-Bar löst die Auswahl serverseitig in die
      **geordnete ID-Liste** auf (`POST applications/bulk-resolve` →
      `Application\ResolveIds`, gleiche Reihenfolge wie die Liste über
      `applyListOrder` im Query-Trait; ungescopt = leer), öffnet die erste
      Bewerbung im Detail. Tests: `BulkResolveEndpointTest` (Reihenfolge,
      all-matching, exclude, trashed, leere Auswahl, Auth).
  - [x] Auswahl bleibt über die Navigation bestehen — **Pinia-`browse`-Store**
        (`start/clear/position/prevId/nextId`, hält nur IDs, In-Session). Einzel-
        Öffnen einer Zeile leert den Browse-Set.
  - [x] Prev/Next-UI in eigener Komponente `Browse.vue`, zentriert im
        `Show.vue`-Header (geteilter `pagination/Button`, Position „3 / 12",
        Grenzen via disabled). `Show.vue` lädt bei `:id`-Wechsel neu (watch, da
        Komponente wiederverwendet wird).
  - PDF-Export der Resultate: siehe §4 (eigener Async-Flow, nutzt dieselbe
    Auswahl-Auflösung).

## 4. Export

**Stand PDF-Export:** **Funktioniert end-to-end als synchroner Download**
(Entscheid Kunde 15.06.: einfacher als der Async-Flow, der die UI stark
verkompliziert). Rendering-Pipeline (`Application\Pdf\Generate` + `Present` +
`Assets` + Blade-Views `pdf.applications`/`_application`/`_styles`/`footer`),
Endpoint `POST applications/bulk-export` (capped) und Frontend-Anbindung sind
erledigt; CLI `app:export-pdf` zum lokalen Prüfen vorhanden.

**Async-Flow entfernt:** Der ursprünglich geplante Async-Weg (Queue-Job
`GeneratePdf` + Tabelle `application_exports`) wurde wieder **entfernt** — die
Code-Artefakte sind gelöscht. Begründung und Referenz-Design für eine spätere
Ausbaustufe (falls der Cap `aporta.exports.max_sync` je stört) stehen im Anhang
am Ende dieser Datei. Die Spatie/Browsershot/Sidecar-Infrastruktur bleibt (der
synchrone Export nutzt sie via `->onLambda()` in Prod).

- [x] **PDF-Export (kompletter Datensatz, 1..n Bewerbungen) — SYNCHRON umgesetzt**
      Erledigt als synchroner Download (Entscheid 15.06., s. „Stand" oben):
  - **Rendering** `Application\Pdf\Generate` (`build()` teilt View/Format/Margins/
    Footer zwischen Disk-Save und Download), `Present` (Model → Anzeige-Array),
    `Assets::fonts()` (base64), Blade `pdf.applications` + `_application` +
    `_styles` + `footer`. `->onLambda()` nur in Prod.
  - **Endpoint** `POST applications/bulk-export` (`ExportRequest`,
    `ApplicationBulkController@export`): Auswahl wie die übrigen Bulk-Aktionen
    auflösen (gescopt, in Listen-Reihenfolge), Cap `aporta.exports.max_sync`
    (Default 100) → sonst 422; leere Auswahl → 422; PDF streamen.
  - **Frontend** `bulkExport()` in `Index.vue` (Blob-Download + Dateiname aus
    `Content-Disposition`; 422-Meldung aus dem Blob lesen). Axios-Interceptor
    überlässt Blob-Fehler dem Aufrufer. API `bulkExport` (`responseType: blob`).
  - **CLI** `app:export-pdf` für lokales Prüfen; **Tests**
    `BulkExportEndpointTest` (ids, all-matching, Cap, leer, ungescopt, Auth).

      Datenumfang = voller Detail-Datensatz wie im `Show`-Action-Eager-Load
      (Haupt-/Mitmieter inkl. Arbeitgeber + aktuelle Wohnsituation, Kinder,
      Wohnungswunsch, Haushalt, Notizen, Status-Verlauf). Auswahl-Auflösung
      wiederverwendet wie bei Bulk-Löschen (`BuildsApplicationListQuery` +
      `ParsesApplicationFilters`).

  - [x] **Setup: Spatie Laravel PDF + Browsershot/Sidecar**
        Erledigt. `spatie/laravel-pdf`, `hammerstone/sidecar`,
        `wnx/sidecar-browsershot` installiert; `puppeteer` als Dev-Dependency
        (lokales Rendering). `config/sidecar.php` + `config/sidecar-browsershot.php`
        publiziert, `BrowsershotFunction` in `config/sidecar.php` registriert.
        Lokaler Browsershot-Render verifiziert. **Cron-taugliches Worker-Modell:**
        Scheduler in `routes/console.php` startet jede Minute einen kurzlebigen
        `queue:work --stop-when-empty` (kein Daemon — passend zur Crontab-only-
        Prod). **AWS/Sidecar end-to-end verifiziert:** Lambda deployed
        (`sidecar:deploy --activate`) und rendert ein echtes PDF (~4.3s Cold-Start).
        AWS-Einrichtung dokumentiert in `.install/sidecar-aws-runbook.md`
        (inkl. zwingender Execution-Role — wird *nicht* automatisch angelegt).
        Prod-Deploy mit `APP_ENV=production` erzeugt eine separate Prod-Funktion.
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

- [x] **Logogrösse prüfen** (ggf. anpassen)
      Erledigt. Logo `h-36` → `h-48`, Header-Padding `py-30` → `py-20`
      (`components/ui/layout/Header.vue`).
- [x] **Rot anpassen** auf den Jam'on-Farbwert (CSS-Variable / Tailwind-Farbe;
      vgl. `text-red` in `Index.vue`)
- [x] **Benutzer-Formular: Button anpassen** (`views/users/`)
      Erledigt. Der „Abbrechen"-Button in `Form.vue` nutzte als einziger
      `variant="outline"`; auf `variant="ghost"` umgestellt, analog zu allen
      anderen „Abbrechen"-Buttons (`Editable.vue`, `NotesPanel.vue`).

---

## Offene Punkte / Rückfragen an Kunde

- Excel-Export: Welche Felder?
- Copy to Clipboard: Welche Felder?
- Automatisches Löschen: Genaue Frist?
- PDF-Layout/Branding: Logo, Schrift, Reihenfolge der Felder.
  (PDF-Ansatz/Library bestätigt: Spatie Laravel PDF + Browsershot/Sidecar.)

---

## Anhang: Async-PDF-Export (verworfen, Referenz-Design)

Der PDF-Export war ursprünglich als **asynchroner Flow** geplant (Queue-Job +
Tracking-Tabelle + Polling + globaler Store + Toast). Umgesetzt wurde stattdessen
ein **synchroner Download** (siehe §4), weil die Async-Maschinerie die UI für den
Normalfall (eine Handvoll angehakter Zeilen) stark verkompliziert hätte.

**Entfernt** (2026-06-15, Code gelöscht):

- `app/Jobs/GeneratePdf.php` — Queue-Job (Auflösung → `Show::loadMany()` →
  `Pdf\Generate` → Disk-Save → Status-Update; `tries`/`backoff`/`failed()`).
- Tabelle `application_exports` (Migration) + Model `ApplicationExport` +
  Enum `ExportStatus` + Factory + Unit-Test.
- Test `GeneratePdfTest`.
- Config-Keys `aporta.exports.disk` / `aporta.exports.ttl_hours`.

**Bleibt bestehen** (auch vom synchronen Export genutzt): die Rendering-Pipeline
(`Application\Pdf\Generate`/`Present`/`Assets`, Blade-Views), `Show::loadMany()` +
`Show::relations()` + `attachPreferenceSlugs()`, die Spatie/Browsershot/Sidecar-
Infrastruktur (Prod-Rendering via `->onLambda()`) und der kurzlebige
`queue:work`-Scheduler (weiterhin für `NotifyNewApplication` nötig).

**Wann reaktivieren:** Falls eine sehr grosse Auswahl (über dem Cap
`aporta.exports.max_sync`, Default 100) synchron zu langsam wird / Timeouts
verursacht. Dann den `Exportieren`-Button auf einen Async-Flow umstellen:

1. Tracking-Tabelle/Model/Enum wieder anlegen (war: `status`, `disk`, `path`,
   `application_count`, `failure_reason`, `expires_at`, `user_id`).
2. Queue-Job, der die Auswahl auflöst, rendert und per `Pdf\Generate::execute()`
   auf einen Disk speichert + Status setzt.
3. Endpoints: `POST bulk-export` (Auftrag anlegen, Job dispatchen, `export_id`
   202 zurück), `GET exports/{export}` (Status-Polling), `GET
   exports/{export}/download` (signiert/temporär).
4. Cleanup-Command (Scheduled): abgelaufene Dateien/Einträge nach ~24h
   (`expires_at`) entfernen.
5. Frontend: app-weiter Export-Store (Polling unabhängig von der Ansicht),
   `bulkExport()` startet den Lauf und übergibt die `export_id` an den Store,
   globales UI-Feedback (Ladehinweis → Toast mit Download-Link bzw. Fehler).
