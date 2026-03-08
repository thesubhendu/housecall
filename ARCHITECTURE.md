## Architecture

This project is a small Laravel 12 API focused on one bounded workflow: receiving referrals from internal systems, triaging them asynchronously, and preserving an audit trail for important lifecycle events.

## Key Design Decisions

- A narrow REST API was used instead of a larger CRUD surface. The exposed operations map directly to the assignment workflow: create, list, show, and cancel referrals.
- Business behavior is separated into small action classes such as referral creation, cancellation, triage, and audit logging. This keeps controllers thin and makes workflow logic easier to test in isolation.
- API responses are normalized through a resource class so all successful responses share a predictable shape.
- Validation is handled with Form Request classes rather than inline controller validation to keep request rules explicit and maintainable.
- Domain enums are used for `priority`, `status`, and audit events to avoid stringly typed business logic.

## Schema Choices

- `referrals` uses a `ULID` primary key. That gives globally unique, sortable identifiers that are suitable for an API-facing resource ID.
- `audit_logs` uses a standard auto-incrementing integer primary key because the table is append-only and internal. This keeps rows smaller than using `ULID` everywhere.
- `idempotency_key` is stored on `referrals` with a unique constraint so duplicate create requests can safely return the original referral.
- Audit log `event` is stored as a short string and metadata is stored as `jsonb` to support flexible event payloads without over-modeling the table.
- Composite indexes were added to support common referral list queries, especially filtering by `status` or `priority` while ordering by creation time.

## Queue And Job Design

- Referral creation dispatches `ProcessReferralTriageJob` so the API stays responsive and triage is handled asynchronously.
- The queue uses Laravel's database driver, which keeps local setup simple and avoids introducing extra infrastructure for this assignment.
- The triage job uses `WithoutOverlapping` keyed by referral ID to reduce concurrent processing of the same referral.
- Triage status transitions are guarded at the database level. The job only moves a referral from `received` to `triaging`, and the triage action only completes when the row is still in `triaging`.
- That state-checking protects against race conditions, including the case where a user cancels a referral while triage is in progress.
- Audit entries are recorded for creation, triage start, triage completion, cancellation, and failed job handling.

## Auth Approach

- Authentication is implemented with a simple internal bearer token checked by custom middleware against `INTERNAL_API_KEY`.
- This was chosen because the API is intended for internal system-to-system communication, so a lightweight shared-token approach is sufficient for the scope of the exercise.
- Using this approach avoids the extra setup and abstraction of user-oriented auth packages when there are no user sessions, permissions, or third-party consumers to manage.

## Tradeoffs

- The triage behavior is intentionally deterministic and simple. Priority drives the outcome logic, which is enough for the assignment but not realistic clinical triage.
- Database queues are easy to run locally, but they are not the best long-term choice for higher throughput or distributed workers.
- The API key model is simple and appropriate for internal use, but it does not support token rotation, per-client credentials, or fine-grained authorization.
- Audit metadata is flexible because it uses JSON, but that comes with less schema enforcement than dedicated event tables.

## What I Would Improve With More Time

- Add request correlation IDs and richer structured logging for easier operational debugging.
- Support token rotation or per-integrator credentials if multiple internal clients were expected.
- Expand audit history exposure through a dedicated endpoint or admin view.
- Make triage rules configurable or strategy-driven instead of embedding the current decision logic directly in the application layer.
- Add more production-focused operational concerns such as queue monitoring, retries with alerting, and rate-limit documentation for integrators.
