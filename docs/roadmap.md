# Roadmap

## Starter (this repository)

GLS HU adapter, immutable normalized points, local atomic catalog, search/lookup, scope locks,
sync command, tests, documentation, CI and implementation prompts.

## Next steps

1. Validate installation in a real Laravel 13 host, scheduler behavior and UI-facing error handling.
2. Add catalog health reporting, explicit refresh events and optional freshness policies.
3. Specify framework-neutral map/list UX, accessibility, lazy loading and tile-provider configuration.
4. Implement optional Livewire 4 bridge without changing core dependencies. Vue/React remain supported.
5. Add DPD after identifying and verifying the intended official or explicitly contracted data source.
6. Design indexed/nearest-point search when real host requirements justify it.

No dates or guaranteed delivery order. Label creation, shipment booking and tracking need
their own contracts and scope decision, potentially in another package.

Before 1.0: publish a stable point/metadata contract, complete all advertised compatibility
checks, test real host installation and document release/migration procedures. Packagist
registration and releases are separate owner actions, not part of this initial scaffold.
