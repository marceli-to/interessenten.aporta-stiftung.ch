# interessenten.aporta-stiftung.ch

Verwaltung der Wohnungs-Bewerbungen (Interessenten) der aporta-Stiftung.
Laravel 12 Backend mit Vue 3 SPA.

## Stack

- **Backend:** Laravel 12, PHP 8.x
- **Frontend:** Vue 3 (SPA), Vue Router, Pinia
- **Styling:** Tailwind CSS
- **Icons:** Phosphor Icons

## Funktionen

- Bewerbungen auflisten, filtern, suchen, sortieren und paginieren
- Detailansicht mit editierbaren Panels (Status, Wohnwunsch, Haushalt, Kinder)
- Status-Workflow (Angemeldet, Verlängert, Archiviert, KNIF) mit Verlauf
- Notizen pro Bewerbung
- Mehrfachauswahl mit Bulk-Aktionen (Löschen, Wiederherstellen, Öffnen)
- Soft-Delete mit „Gelöscht"-Ansicht
- Öffentliche Intake-API (`/api/v1/applications`), die Formular-Einreichungen entgegennimmt

## Architektur

- **API-Routen:** `routes/api.php` — Dashboard unter `/api/dashboard/`, Intake unter `/api/v1/`
- **Actions:** `app/Actions/` — Single-Responsibility-Klassen
- **Vue SPA:** `resources/js/app/` — Views, Components, Stores, API-Layer
- **Composables:** `resources/js/app/composables/` — z. B. `useListQuery`, `useListSelection`

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Entwicklung

```bash
npm run dev
```
