# Source notes

Checked during initial development on 2026-10-19:

- GLS Hungary integration overview: https://gls-group.com/HU/hu/webshop-integracio/
- GLS Hungary delivery-point feed: https://map.gls-hungary.com/data/deliveryPoints/hu.json
- Laravel package development: https://laravel.com/framework/docs/13.x/packages
- Laravel HTTP client: https://laravel.com/framework/docs/13.x/http-client

The live GLS feed was retrieved to confirm field names, coordinates and point types. It is
not committed; tests use independently authored synthetic fixtures. The feed URL is observed
working behavior, not a documented stability guarantee. Confirm carrier data use terms and
supported countries before distributing data or adding services.

The supplied WooCommerce plugin was a behavioral reference for separating catalog refresh,
selection and shipment operations. Its code, logos, styles and frontend assets are not part of
this repository. In particular, its DPD catalog uses a Kvikk service; no such dependency is
included here. This repository currently implements no DPD adapter.
