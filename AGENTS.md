# Development instructions

## Scope

Read `docs/specification.md`, `docs/architecture.md` and relevant ADRs before modifying contracts.
This is a PHP 8.4+ Laravel 12/13 library, not a host shop or a WordPress port.
Prefer Laravel 13, PHP 8.5 and Pest 5; Laravel 12 development dependencies use Pest 4.

## Required workflow

- Use TDD for runtime behavior: demonstrate the missing behavior with a failing test, implement,
  then refactor. Test contracts and outcomes, not copies of internal code.
- Run `composer validate --strict` and `composer check` before completion.
- Source uses `declare(strict_types=1)`, PSR-4 and Laravel Pint. Larastan level 8 must pass
  without adding blanket ignores or baselines. Keep dependencies explicit.
- Use HTTP fakes and synthetic fixtures. Tests must not access the network or production data.
- Do not change order, payment or shipment behavior in this package. No hidden HTTP routes,
  scheduler jobs, external telemetry, database migrations or UI dependencies.
- Keep UI optional. Never add Livewire, React, Vue or map libraries to required core dependencies.
- Preserve immutable DTOs, provider-country isolation and string external identifiers.
- Never replace a good catalog with failed/partial/empty input. Publish atomically and release locks.
- Do not trust browser-supplied addresses or use request input as provider configuration/URLs.
- Render provider text escaped in examples and integrations.
- Never copy code, logos, styles or assets from the supplied GPL WooCommerce reference plugin.
- Do not introduce Kvikk or another intermediary without an explicit documented source decision.
- Document changed public contracts and add an ADR for significant architecture changes.
- Do not publish Packagist releases, tags or merge external PRs unless requested.

## Completion report

Summarize behavior, test results, compatibility tested and remaining limitations. Distinguish
locally run checks from CI-only coverage. Do not claim unimplemented planned features.
