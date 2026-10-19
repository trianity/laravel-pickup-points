# ADR 0001: Frontend-independent catalogs with optional UI

Status: accepted.

Use the package name `trianity/laravel-pickup-points`, namespace `Trianity\PickupPoints`.
Require PHP 8.4+, support Laravel 12/13 and prefer PHP 8.5/Laravel 13.

The package provides pickup-point data, not a checkout or general shipping platform.
Keep Livewire out of required dependencies. Vue/React consumers access a host-defined JSON
endpoint. A Livewire 4 integration is a separate opt-in layer to be designed later.

Start with GLS Hungary. Adapters implement an explicit contract and are registered through
configuration/container resolution. Country support is declared by each adapter.

Use a JSON repository initially to avoid database migrations and vendor-specific engines.
Expose a repository contract for future indexed/shared storage. Keep old data on refresh
failure and expose its timestamp; freshness policy belongs to the host application.

Use MIT for original implementation. Reference material informs behavior only. Do not copy
WordPress plugin code/assets or use its developer's services as undeclared dependencies.

Consequences: installations stay small; integration with a checkout is explicit. The initial
text search is linear and not a complete map/spatial API. Shipping labels are separate work.
