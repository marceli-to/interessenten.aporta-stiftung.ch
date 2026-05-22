# cms.marceli.to

[![Tests](https://github.com/marceli-to/cms.marceli.to/actions/workflows/tests.yml/badge.svg)](https://github.com/marceli-to/cms.marceli.to/actions/workflows/tests.yml)

A lightweight, custom-built CMS powered by Laravel 12, Vue 3, and Tailwind CSS.

## Stack

- **Backend:** Laravel 12, PHP 8.x
- **Frontend:** Vue 3 (SPA), Vue Router, Pinia
- **Styling:** Tailwind CSS
- **Icons:** Phosphor Icons
- **Editor:** TipTap
- **Upload:** Uppy
- **Drag & Drop:** vuedraggable

## Modules

### Blog
- Create, edit, and delete posts
- Rich text editor (TipTap)
- Publish/draft toggle
- Per-post media gallery with drag & drop reordering
- Teaser image selection

### Media
- Central media library with grid view
- Upload via drag & drop or file picker (JPG, PNG, WebP, GIF — max 50 MB)
- Edit alt text and captions via slide-in drawer
- Search/filter by filename, alt text, or caption
- Delete protection for images in use ("Verwendet" badge)
- Teaser toggle (set/unset)

## Architecture

- **API routes:** `routes/api.php` — RESTful endpoints under `/api/dashboard/`
- **Actions:** `app/Actions/` — single-responsibility action classes
- **Vue SPA:** `resources/js/app/` — views, components, stores, API layer
- **Reusable components:** `PageHeader`, `FormButton`, `FormGroup`, `MediaUploader`, `MediaGrid`, `MediaEditModal`, `Editor`, `ConfirmDialog`, `Toast`

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Development

```bash
npm run dev
```
