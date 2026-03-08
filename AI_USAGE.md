## AI Usage

I used `Cursor` as an implementation assistant during this take-home assignment. I treated it as a tool for accelerating drafting, exploring options, and reviewing tradeoffs, while keeping final technical decisions and verification under my control.

### Where It Was Helpful

- Quickly scaffolding parts of the implementation so I could focus more time on API behavior, data modeling, and edge cases.
- Comparing identifier and schema options such as `ULID` vs `UUID` vs integer IDs.
- Suggesting indexing improvements, including composite indexes to support expected filtering and search patterns.
- Speeding up iteration on authentication, migrations, and referral workflow code.

### One Thing It Got Wrong or Incomplete

- It initially pushed some decisions further than necessary. For example, it added `ULID` usage for audit logs, which I judged unnecessary for that table because it increases storage size without enough practical benefit for this assignment.
- It also suggested using `Sanctum` for system-to-system API communication. I replaced that with a simpler internal token-based approach because it better matched the actual requirements and kept the solution more focused.
- In one migration draft, the table creation order was wrong: `audit_logs` was created before the related referral table it referenced, which would fail due to foreign key constraints. I corrected the migration order manually.

### What I Manually Verified or Changed

- I changed the audit log status column to `VARCHAR(30)` to keep the schema tighter and avoid unnecessary database space usage.
- I removed or avoided AI-suggested complexity where it did not improve the solution, including the extra `ULID` usage on audit logs.
- I replaced the suggested `Sanctum` approach with a simple internal token-based authentication mechanism appropriate for internal API access.
- I fixed the migration ordering issue so foreign key dependencies are created in the correct sequence.
- I corrected referral cancellation logic where a queued triage job could incorrectly move a cancelled referral back to `accepted`.
- I added composite indexes after reviewing likely query patterns and performance considerations.

Overall, AI helped speed up drafting and comparison of approaches, but I manually reviewed the design, corrected incorrect assumptions, simplified parts that were over-engineered, and verified behavior against the assignment requirements.
