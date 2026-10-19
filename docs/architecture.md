# Architecture

## Main components

| Component | Responsibility |
| --- | --- |
| `PickupPointProvider` | Declare ID/countries and fetch a complete normalized source snapshot |
| `ProviderRegistry` | Resolve enabled adapters and enforce supported scopes |
| `SyncCatalog` | Acquire scope lock, fetch, validate full snapshot and publish it |
| `CatalogRepository` | Read/atomically replace a complete catalog |
| `JsonCatalogRepository` | Private local JSON, schema version 1, atomic rename |
| `PickupPoints` | Local lookup, bounded text/type search, sync metadata |
| `PickupPointsServiceProvider` | Configuration, container bindings and console registration |

There is one JSON document per scope containing `schema_version`, `provider`, `country`,
`synced_at` and `points`. Publishing uses a checked temporary-file write and
rename in the same directory. A reader opens one document; there is no separate manifest
that could get out of sync with the points. The filesystem must provide atomic rename.

The default repository is appropriate for a single host/shared local filesystem. It does not
implement multi-region replication, object-storage rename semantics or fsync-based crash
durability. A different repository can be bound through the container contract.

`SyncCatalog` serializes writes with a cache lock. The lock is not a read lock: readers keep
using the previous snapshot during a download. Custom adapters must respect the configured
lock lifetime; extending it is necessary for long imports. A crashed worker's lock expires.

The starter loads a scope into memory and scans it for lookup/search. This keeps installation
simple for a few thousand points. Indexed or spatial repositories and pagination can be
introduced when required; no database or spatial engine is imposed on hosts today.

## Extension example

```php
use Trianity\PickupPoints\Contracts\PickupPointProvider;

final class ExampleProvider implements PickupPointProvider
{
    public function id(): string { return 'example'; }
    public function countries(): array { return ['HU']; }
    public function fetch(string $country): array
    {
        // Fetch your source, validate it, return a list<PickupPoint>.
        // Throw if incomplete. Do not write files or mutate checkout state here.
    }
}
```

Register its class in `pickup-points.providers`. Required dependencies are constructor-injected
by Laravel. Register custom repository bindings after the package provider, in the host's
service provider. Scope and DTO invariants apply to all adapters.

## UI direction

A future framework-neutral map should consume normalized JSON and emit a selected identity.
The optional Livewire 4 bridge may use that component, but core PHP classes must not import
Livewire. UI state and host order models are never dependencies of provider adapters.
