# Laravel Pickup Points

Frontend-independent pickup-point catalogs for Laravel, with extensible carrier adapters.

**Initial development version.** The starter implements GLS Hungary catalog synchronization,
local storage, point lookup and text search. Public API contracts may change before 1.0.
There is no Livewire, Vue, React, map, database or checkout dependency.

## Requirements

- PHP 8.4+ with mbstring; PHP 8.5 preferred.
- Laravel 12 or 13; Laravel 13 preferred.
- A writable private local directory and a Laravel cache store supporting atomic locks.

## Install from GitHub

Until a tagged Packagist release exists:

```bash
composer config repositories.pickup-points vcs https://github.com/trianity/laravel-pickup-points
composer require trianity/laravel-pickup-points:dev-main
php artisan vendor:publish --tag=pickup-points-config
php artisan pickup-points:sync gls --country=HU
```

The service provider is auto-discovered. No migration is required.

## Read the local catalog

```php
use Trianity\PickupPoints\Enums\PointType;
use Trianity\PickupPoints\PickupPoints;

$catalog = app(PickupPoints::class);

$shops = $catalog->search('gls', 'HU', query: 'Budapest', type: PointType::Shop);
$lockers = $catalog->search('gls', 'HU', type: PointType::Locker, limit: 100);
$point = $catalog->find('gls', $selectedPointId, 'HU');
$lastSyncedAt = $catalog->snapshot('gls', 'HU')->syncedAt;
```

Reads never call the carrier. A missing or corrupt catalog throws `CatalogException`;
an unknown point in a valid catalog returns `null`. Search returns up to 50 results by
default (maximum 500), in source order; it is Unicode case-insensitive, not geospatial
or accent-insensitive. Use `snapshot()->points` when an entire catalog is needed internally.

Schedule refresh in the **host application**, for example in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('pickup-points:sync gls --country=HU')->dailyAt('03:15')->withoutOverlapping();
```

The host must run Laravel's scheduler and monitor failed executions. Sync failures have a
non-zero command exit code and leave the last successfully published catalog intact.
The package does not register schedules or silently retry indefinitely.

## Vue, React and other frontends

The point DTO implements `JsonSerializable`. Your own controller can return
`response()->json(['data' => $catalog->search(...)])`. No JavaScript framework is required.
See [the HTTP integration example](docs/http-integration.md) for validated input and
server-side lookup. The package does not automatically expose public HTTP endpoints.

At checkout, accept only the provider/country/point ID from the browser, look up the
canonical point on the server, apply your shipping/payment rules and save the point's
address snapshot with the order. Treat carrier names and descriptions as untrusted text.
Catalog membership does **not** guarantee that a point currently accepts the shipment;
temporary closures and provider metadata require an application policy.

## Add an adapter

Implement `Contracts\PickupPointProvider`, then add its container-resolvable class:

```php
'providers' => [
    'gls' => \Trianity\PickupPoints\Providers\GlsProvider::class,
    'your-carrier' => \App\Shipping\YourCarrierProvider::class,
],
```

The configuration key must match `id()`. The adapter declares supported uppercase country
codes and returns normalized `PickupPoint` objects. Duplicate IDs, mixed scopes and empty
catalogs are rejected. You may replace `Contracts\CatalogRepository` through your service
provider for another storage backend. See [architecture](docs/architecture.md).

## Operational boundaries

- The default repository uses private JSON files and same-directory atomic rename.
  CLI and web processes need access to the same directory. Files are created with mode
  `0600`; run under the same user, or implement a deployment-specific repository.
- Default GLS support is Hungary. Other countries require explicit configuration and
  verification against their feed and commercial service availability.
- The configured GLS URL is deployment configuration, never browser input. HTTPS is required;
  redirects are rejected. One bounded-time GET is made per sync; later scheduler runs retry.
- The response size limit is checked after the HTTP body has been received; it is not a
  streaming download limit. The source must be trusted.
- The sync lock lasts 120 seconds by default. Keep it longer than the maximum complete sync,
  including custom adapter work. Multi-node deployments need a shared lock store and a
  repository providing a consistent shared catalog. An array cache store only locks within
  one process and is intended for tests.
- Old successful catalogs remain readable during upstream outages. `syncedAt` allows the
  host to alert or enforce its own maximum-age policy. No freshness cutoff is enforced here.

## Development

```bash
composer install
composer check
```

`composer check` runs Pint, Larastan level 8 and Pest. Tests use synthetic fixtures and
prevent accidental external HTTP calls. The CI matrix covers PHP 8.4/8.5 and Laravel 12/13;
Laravel 12 uses Pest 4, while Laravel 13 uses Pest 5 because their Laravel plugins differ.

Start with [the specification](docs/specification.md), [decisions](docs/decisions/0001-package-boundaries.md)
and [AI implementation tasks](.ai/prompts/README.md).

## Scope and roadmap

| Available in the starter | Planned separately |
| --- | --- |
| GLS Hungary shop/locker import | DPD after an approved source is identified |
| Atomic local catalog replacement | Optional framework-neutral map UI |
| Lookup and bounded text/type search | Optional Livewire 4 integration |
| Adapter and repository contracts | Nearest-point/geospatial search |
| Sync command, tests and CI | Carrier eligibility policies |

Shipping rates, orders, payment, tax, label creation and shipment booking are outside this
starter's scope. See [the roadmap](docs/roadmap.md).

## Support and license

MIT. Provided without warranty. Issues and contributions are welcome; response times and
individual integration assistance are not guaranteed. Commercial implementation or support
may be arranged separately. This is an independent package, not an official GLS product.
The code license does not grant rights to carrier data, trademarks or third-party services.

No WordPress/WooCommerce plugin code or assets are included. Implementation is independent;
synthetic fixtures follow the observed GLS data shape. See [source notes](docs/sources.md).
