# Housecall - Patient Referral Intake API

Backend REST API for accepting patient referrals from internal systems, storing them durably, and processing triage asynchronously with Laravel queues.

## Project Overview

The API supports four main workflows:

- create a referral
- list referrals with filtering and pagination
- retrieve a single referral
- cancel a referral while it is still cancellable

Each newly created referral starts in `received` status and dispatches a queue job to simulate downstream triage. Audit log rows are recorded for important lifecycle events such as creation, triage start, triage completion, and cancellation.

## Prerequisites

- PHP 8.2+
- Composer
- PostgreSQL 14+
- Node.js and npm only if you want to build frontend assets or run the combined local dev script

## Local Setup Without Docker

### 1. Install dependencies

```bash
composer install
```

### 2. Create the environment file

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your local PostgreSQL credentials and ensure the queue driver is set to the database driver:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=housecall
DB_USERNAME=your_user
DB_PASSWORD=your_password
QUEUE_CONNECTION=database
```

### 3. Generate an internal API token

Generate a token:

```bash
php artisan tinker --execute="echo Str::random(64);"
```

Add it to `.env`:

```env
INTERNAL_API_KEY=your-generated-token
```

## Run Migrations

```bash
php artisan migrate
```

This creates the application tables plus the database-backed queue tables used by the triage workflow.

## Run The App

Start the API server:

```bash
php artisan serve
```

The application will be available at `http://127.0.0.1:8000`.

## Run The Queue Worker

In a separate terminal, start a worker so newly created referrals can move through triage:

```bash
php artisan queue:work --queue=default --tries=3 --backoff=30
```

If you prefer a single command for local development, the project also includes:

```bash
composer run dev
```

That starts the HTTP server, queue listener, log tailing, and Vite dev server together.

## Run Tests

```bash
php artisan test --compact
```

Tests run against an in-memory SQLite database via `phpunit.xml`, so they do not require your local PostgreSQL database.

## Authentication

All API routes require a bearer token:

```http
Authorization: Bearer <INTERNAL_API_KEY>
```

This is intended for internal system-to-system access, not end-user authentication.

## API Summary

Base path:

```text
/api/v1
```

### Create Referral

```text
POST /api/v1/referrals
```

Headers:

| Header | Description |
|---|---|
| `Authorization` | `Bearer <token>` |
| `X-Idempotency-Key` | Optional. Reusing the same key returns the original referral with `409 Conflict`. |

Request body:

```json
{
  "patient_name": "Jane Doe",
  "patient_date_of_birth": "1990-05-15",
  "patient_external_id": "PAT-1234",
  "referral_reason": "Chest pain evaluation",
  "priority": "high",
  "referring_party": "City General Hospital",
  "notes": "Patient reports intermittent chest pain for 3 weeks."
}
```

### List Referrals

```text
GET /api/v1/referrals
```

Supported query parameters:

| Parameter | Description |
|---|---|
| `status` | `received`, `triaging`, `accepted`, `rejected`, `cancelled` |
| `priority` | `low`, `medium`, `high`, `urgent` |
| `referring_party` | Prefix match filter |
| `patient_external_id` | Exact match filter |
| `created_from` | Inclusive start date |
| `created_to` | Inclusive end date |
| `sort` | `created_at` or `updated_at` |
| `order` | `asc` or `desc` |
| `per_page` | Pagination size from `1` to `100` |

### Get Referral

```text
GET /api/v1/referrals/{id}
```

### Cancel Referral

```text
POST /api/v1/referrals/{id}/cancel
```

Optional request body:

```json
{
  "reason": "Patient no longer requires referral."
}
```

Cancellation is allowed only while the referral is in `received` or `triaging` status.

## Response Shape

Successful responses are wrapped in a `data` key:

```json
{
  "data": {
    "id": "01HV...",
    "status": "received",
    "priority": "high",
    "patient": {
      "name": "Jane Doe",
      "date_of_birth": "1990-05-15",
      "external_id": "PAT-1234"
    },
    "referral_reason": "Chest pain evaluation",
    "referring_party": "City General Hospital",
    "notes": "Patient reports intermittent chest pain for 3 weeks.",
    "triage_notes": null,
    "cancelled_reason": null,
    "cancelled_at": null,
    "created_at": "2026-03-07T10:00:00+00:00",
    "updated_at": "2026-03-07T10:00:00+00:00"
  }
}
```

Example error response:

```json
{
  "message": "Referral cannot be cancelled from status [accepted].",
  "error": "referral_not_cancellable"
}
```

