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
  - [ ] **Aktion: Alle (ausgewählten) löschen** (Backend `{ids|filters}` +
        Bestätigung mit echter Anzahl; Soft-Delete als Sicherheitsnetz)
  - [ ] **Aktion: Alle (ausgewählten) exportieren** (hängt an Export-Klärung, §4)

### Teil B – Resultatansicht / Browse (Folge-Task)

- [ ] **Resultatansicht: angehakte Bewerbungen durchblättern**
      „Öffnen" in der Bulk-Bar öffnet die erste ausgewählte Bewerbung im
      Detail und blendet dort eine Prev/Next-Navigation ein, die **nur durch
      die angehakten Zeilen** blättert (Entscheid: nicht das ganze Filter-Set).
  - [ ] Auswahl muss über die Navigation hinweg bestehen bleiben
        (Ansatz offen: Pinia-„Browse-Set"-Store vs. URL vs. Hybrid).
  - [ ] Prev/Next-UI in `Show.vue` (Position „3 / 12", Grenzen abfangen).
  - [ ] PDF-Export der Resultate (hängt an PDF-Klärung, §4).

## 4. Export

- [ ] **PDF-Export (kompletter Datensatz)**
      Vollständigen Datensatz einer Bewerbung als PDF exportieren.
      → Klären: PDF-Library/Ansatz (z.B. dompdf / Browsershot).

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
