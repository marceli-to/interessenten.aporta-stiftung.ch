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

- [ ] **Filter Einkommen (Brackets)**
      Einkommens-Filter mit Bereichen (Brackets) ergänzen – analog zu Miete
      (`rent_min`/`rent_max`), aber als vordefinierte Brackets. Sowohl Filter-UI
      als auch API-Query.

## 2. Darstellung

- [x] **Telefonnummer mit Abständen formatieren**
      Erledigt. Neuer `fmtPhone()` in `utils/format.js`: gruppiert in E.164
      gespeicherte CH-Nummern zu `+41 79 409 49 27`; Nicht-CH/unparsbare Werte
      werden unverändert angezeigt. Eingesetzt in der Anzeige von `ApplicantPanel`
      (mobile_phone) und `HousingPanel` (landlord_phone). Edit-Felder bleiben auf
      dem rohen Wert (Backend-Normalizer macht beim Speichern wieder E.164).

## 3. Listenansicht & Multiedit

- [ ] **Listenansicht mit Checkbox-Multiedit erweitern**
      In `Index.vue` Auswahl-Checkboxen pro Zeile + „Alle auswählen" ergänzen.
  - [ ] **Aktion: Alle (ausgewählten) löschen**
  - [ ] **Aktion: Alle (ausgewählten) exportieren**

- [ ] **Resultatansicht**
      Ausgewählte/gefilterte Resultate öffnen lassen (Browse) und als PDF exportieren.

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
