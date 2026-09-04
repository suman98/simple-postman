# API Bench

A self-hosted HTTP request client — compose a request, send it, read the response. Built to run server-side (from the Laravel backend, not the browser) so CORS never blocks a test.

## What it does

- Compose requests: method (GET/POST/PUT/PATCH/DELETE), URL, headers, and JSON or form parameters.
- Send instantly from **Quick Test** — no account, project, or save required. State persists to `localStorage` only.
- Or organize saved requests into **Projects → Endpoints**, persisted via Eloquent/SQLite.
- Read the response as received: status code, duration, response headers, and body (JSON pretty-printed, raw text otherwise) — never smoothed or reformatted to look nicer than it is.

See [PRODUCT.md](PRODUCT.md) for product intent and [DESIGN.md](DESIGN.md) for the visual design system.

## Stack

- Laravel 13 (PHP 8.3+), SQLite
- Blade + Alpine.js + Tailwind v4 (via Vite) — no SPA framework
- CodeMirror 6 for the JSON body editor and response viewer

## Routes

| Route | Purpose |
|---|---|
| `GET /quick-test` | Zero-setup request composer |
| `POST /api/run` | Executes the request server-side, returns the response |
| `resource /projects` | CRUD for projects |
| `resource /projects/{project}/endpoints` | CRUD for saved endpoints within a project |

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
```

Run the app:

```bash
composer dev
```

This runs the Laravel server, queue listener, and Vite dev server together. Visit `http://localhost:8000`.

## Tests

```bash
php artisan test
```
