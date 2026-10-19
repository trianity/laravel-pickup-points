# Contributing

Use PHP 8.4+ and Composer 2. Install dependencies with `composer install`; run
`composer validate --strict` and `composer check`. Laravel 13/Pest 5 is the default development
combination. CI also exercises Laravel 12/Pest 4 on PHP 8.4/8.5.

Report a reproducible defect with package commit/version, PHP/Laravel versions, provider,
country, cache/repository configuration and a minimal example. Remove customer data and secrets.
An issue is not a paid support request and has no guaranteed response or resolution time.

For new carriers, open a design issue describing the authorized source, country support,
identity scheme and sample synthetic payload before submitting a large implementation.
Adapters need normalization and failed-refresh regression tests. Keep UI and checkout concerns
out of the core. Contributions must be original or compatible with the MIT license.
