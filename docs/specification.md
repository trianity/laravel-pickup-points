# Initial specification

Status: implemented starter, pre-1.0. Language of source code, public documentation and AI tasks: English.

## Goal

Provide a reusable Laravel service for local pickup-point catalogs. The host may use Blade,
Livewire, Vue, React, a mobile app or server-to-server calls. Extending the carrier list must
not require editing GLS code, checkout models or frontend components.

## Runtime contracts

1. Scope is `(provider, country)`; point identity is `(provider, country, external ID)`.
   IDs and postcodes remain strings. Country codes are normalized to uppercase at service boundaries.
2. `PickupPoint` is an immutable JSON-serializable DTO. Shop and locker are explicit types.
   Unknown source types are rejected, not silently classified as shops.
3. Coordinates must be finite and within geographic bounds. Required address and name fields
   must be non-empty. Features are source-specific tokens; absence does not imply false.
4. Availability hints, opening hours and holidays are retained as provider metadata.
   They are not a universal shipment eligibility guarantee.
5. A complete sync validates the entire response and duplicate IDs before publishing.
   Invalid, empty, partial or failed responses must not replace a successful catalog.
6. Reads return one complete old or new snapshot. They must not observe partial writes.
7. Concurrent sync for the same scope is rejected using an atomic cache lock. Lock ownership
   is released after success and failure. Lock lifetime is a deployment responsibility.
8. Reads never fetch remote data. Missing/corrupt catalogs are explicit errors; an absent ID
   in a valid catalog returns null. Successful but old catalogs remain available.
9. The host owns scheduling, alerting, freshness limits, checkout state, allowed shipping
   methods, COD rules and order persistence. A browser-provided address is not authoritative.
10. Provider configuration and custom repository bindings must be possible without a fork.

## First adapter: GLS Hungary

Read GLS's deliveryPoints JSON over HTTPS, map `parcel-shop` / `parcel-locker`, preserve
external IDs, coordinates, features and selected operational metadata. No MyGLS credentials
or label API are required for catalog import. The point-data endpoint is an observed source,
not a promise of a permanently stable public API.

## Acceptance evidence

- Synthetic-fixture tests cover normalization, lookup, Unicode search, type/limit filtering,
  country separation, invalid identifiers, duplicate IDs and malformed/failed imports.
- Failure tests compare the catalog file before and after unsuccessful synchronization.
- Lock contention/release, corrupt local files, command exit codes and adapter extension
  are covered by tests. No test depends on a live service.
- Pint, Larastan level 8 and the PHP/Laravel CI matrix must pass before a stable release.

## Not in the initial implementation

No public routes, UI components, order tables, DB migrations, automatic scheduler registration,
shipment creation, rate calculation, label printing, map tiles or geocoding services.
Livewire 4 will be optional; a Livewire integration must not become a core dependency.
DPD must not implicitly rely on the reference plugin developer's Kvikk endpoint.
