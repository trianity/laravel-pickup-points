# Task 02 — Catalog health and lifecycle events

Read AGENTS.md and all current catalog contracts. Implement with TDD:

1. Add explicit serializable catalog health information: scope, presence, point count,
   last successful sync timestamp and age. Distinguish missing, invalid and old catalogs.
2. Add a read-only `pickup-points:status` command, with documented exit codes and optional
   maximum-age policy. Use an injectable clock so boundary tests are deterministic.
3. Add typed sync success/failure events without exposing credentials or raw remote bodies.
   Listener failures must not misreport a successfully published catalog as rolled back.
4. Keep reads available during upstream outages by default. Do not silently add a TTL,
   delete old data or fetch remotely during lookup. The host chooses its freshness policy.
5. Test timestamp boundaries, failed refresh after success, storage failure and missing catalog.

Update docs and run the full supported checks. Do not start map UI or DPD in this task.
