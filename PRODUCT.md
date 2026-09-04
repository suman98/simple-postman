# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Developers testing and debugging HTTP APIs while building something else. They arrive mid-task — a request just failed, a payload shape is unclear, an endpoint needs a sanity check — and they want an answer in seconds, then to leave. Two distinct situations: a throwaway one-off check (no setup tolerated), and repeated runs of the same endpoint across a work session or project.

## Product Purpose

Compose an HTTP request (method, URL, headers, JSON or form parameters), send it, and read the response. Success is a correctly-formed request going out and a legible response coming back with as little ceremony as possible. Named "API Bench" in the current interface.

## Positioning

A self-hosted request bench that runs the request **server-side**, from the app's own Laravel backend rather than the browser — so CORS never blocks a test and no account, workspace, sync, or install stands between the developer and the first request. The Quick Test surface is deliberately zero-commitment: no project required, nothing saved to a database.

## Operating Context

Runs locally (`php artisan serve`) alongside the developer's editor and terminal, usually on a desktop screen in a lit room, frequently in a second window next to the code that calls the API. Sessions are short and interruptive. Work is either ad-hoc (Quick Test) or organized into projects containing saved endpoints.

## Capabilities and Constraints

- Methods: GET, POST, PUT, PATCH, DELETE.
- Parameters as either raw JSON body or key/value form data; GET/DELETE send params as query string, POST/PUT/PATCH as body.
- Arbitrary request headers as key/value rows.
- Response returns status code, duration in ms, response headers, and body (parsed JSON when the content type says JSON, raw text otherwise). Response headers are returned by the backend today.
- Quick Test persists the last sent request to `localStorage` only; nothing about it is written to the database.
- Projects and endpoints persist to SQLite via Eloquent (`Project hasMany Endpoint`).
- CodeMirror 6 provides the JSON body editor (highlighting, lint, format) and the read-only response viewer.
- No authentication, no multi-user, no request history beyond the single last Quick Test call.
- Stack constraint: Laravel Blade + Alpine.js + Tailwind v4 via Vite. No SPA framework.

## Brand Commitments

Name in the interface is "API Bench". No logo or wordmark exists.

**Standing preference (confirmed by the user):** the interface follows the conventional request-client pattern, with Postman named as the reference and craft bar. Plain, standard product language only — Send, Params, Body, Headers, Response, Status, Time, Size. No themed vocabulary, metaphors, or invented nouns for standard HTTP concepts. A themed visual world (customs/postal forms) was built and explicitly rejected; do not reintroduce that or any equivalent conceit.

## Evidence on Hand

The working application itself is the only asset: real requests to real endpoints return real responses. There are no customers, benchmarks, testimonials, or pricing — none may be fabricated. Demonstration requests in screenshots use public test endpoints (httpbin.org).

## Product Principles

1. **The first request costs nothing.** Quick Test must never require setup, naming, or saving.
2. **Server-side execution is the point.** Anything a browser-based tester cannot reach, this can.
3. **Response legibility is the product.** Reading the result is the job; formatting, highlighting, and copying it are core, not decoration.
4. **Short, interrupted sessions.** State that survives a reload (last call, saved endpoints) beats state a user must recreate.
5. **Never lie about a response.** Status, timing, headers, and body are shown as received, never smoothed.

## Accessibility & Inclusion

No product-specific requirement established beyond standard web accessibility.
