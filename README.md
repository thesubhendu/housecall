# Housecall — Patient Referral Intake API

A backend REST API for patient referral intake and triage, built with Laravel 12.

## Overview

This service allows internal systems to submit, retrieve, list, and cancel patient referrals. New referrals automatically trigger an asynchronous triage workflow via Laravel queues.

## Prerequisites

- PHP 8.2+
- Composer
- PostgreSQL 14+
- Node.js (for asset compilation, not required for API-only usage)

## Local Setup (without Docker)

### 1. Install dependencies

```bash
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your PostgreSQL credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=housecall
DB_USERNAME=your_user
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
```

### 3. Run migrations

```bash
php artisan migrate
```

### 4. Generate and set the API key

Generate a secure random key:

```bash
php artisan tinker --execute="echo Str::random(64);"
```

Add it to your `.env`:

```env
INTERNAL_API_KEY=your-generated-key-here
```

### 5. Start the application

```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`.

### 6. Run the queue worker

In a separate terminal:

```bash
php artisan queue:work --queue=default --tries=3 --backoff=30
```

## Running Tests

```bash
php artisan test --compact
```

## API Reference

All endpoints require an `Authorization: Bearer <key>` header, where `<key>` is the value of `INTERNAL_API_KEY` in your `.env`.

### Base URL

```
/api/v1
```

---

### Create Referral

```
POST /api/v1/referrals
```

**Headers**

| Header | Description |
|---|---|
| `Authorization` | `Bearer <token>` |
| `X-Idempotency-Key` | Optional. UUID string. Prevents duplicate submissions on retry. |

**Request Body**

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

| Field | Type | Required | Description |
|---|---|---|---|
| `patient_name` | string | Yes | Full patient name |
| `patient_date_of_birth` | date (YYYY-MM-DD) | Yes | Must be in the past |
| `patient_external_id` | string | No | External system patient ID |
| `referral_reason` | string | Yes | Clinical reason for the referral |
| `priority` | enum | Yes | `low`, `medium`, `high`, `urgent` |
| `referring_party` | string | Yes | Name of the source system or clinician |
| `notes` | string | No | Additional context |

**Responses**

- `201 Created` — referral created successfully
- `409 Conflict` — duplicate idempotency key; returns the existing referral

---

### Get Referral

```
GET /api/v1/referrals/{id}
```

**Responses**

- `200 OK`
- `404 Not Found`

---

### List Referrals

```
GET /api/v1/referrals
```

**Query Parameters**

| Param | Type | Description |
|---|---|---|
| `status` | string | Filter by status: `received`, `triaging`, `accepted`, `rejected`, `cancelled` |
| `priority` | string | Filter by priority: `low`, `medium`, `high`, `urgent` |
| `referring_party` | string | Partial match on referring party name |
| `per_page` | integer | Results per page (1–100, default 15) |

**Response**

Paginated collection with `data`, `links`, and `meta`.

---

### Cancel Referral

```
POST /api/v1/referrals/{id}/cancel
```

**Request Body** (optional)

```json
{
  "reason": "Patient no longer requires referral."
}
```

**Responses**

- `200 OK` — referral cancelled
- `422 Unprocessable` — referral is not in a cancellable state (`accepted`, `rejected`, or already `cancelled`)

---

### Response Shape

All successful responses wrap data in a `data` key:

```json
{
  "data": {
    "id": "01HXYZ...",
    "status": "received",
    "priority": "high",
    "patient": {
      "name": "Jane Doe",
      "date_of_birth": "1990-05-15",
      "external_id": "PAT-1234"
    },
    "referral_reason": "Chest pain evaluation",
    "referring_party": "City General Hospital",
    "notes": "...",
    "triage_notes": null,
    "cancelled_reason": null,
    "cancelled_at": null,
    "created_at": "2026-03-07T10:00:00+00:00",
    "updated_at": "2026-03-07T10:00:00+00:00"
  }
}
```

### Error Shape

```json
{
  "message": "Referral cannot be cancelled from status [accepted].",
  "error": "referral_not_cancellable"
}
```

## Sample curl Requests

```bash
TOKEN="your_bearer_token_here"

# Create
curl -X POST http://127.0.0.1:8000/api/v1/referrals \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Idempotency-Key: $(uuidgen)" \
  -d '{"patient_name":"Jane Doe","patient_date_of_birth":"1990-05-15","referral_reason":"Chest pain","priority":"high","referring_party":"City Clinic"}'

# List
curl http://127.0.0.1:8000/api/v1/referrals?status=received \
  -H "Authorization: Bearer $TOKEN"

# Get
curl http://127.0.0.1:8000/api/v1/referrals/{id} \
  -H "Authorization: Bearer $TOKEN"

# Cancel
curl -X POST http://127.0.0.1:8000/api/v1/referrals/{id}/cancel \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"reason":"Duplicate submission."}'
```
