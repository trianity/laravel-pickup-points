# Task 03 — Specify and implement an optional framework-neutral picker

First inspect the current core and propose a short UI ADR. Then implement the agreed minimal
picker as an optional integration using the normalized catalog API.

Requirements: responsive map plus searchable list; shop/locker filters; lazy asset loading;
keyboard navigation and labelled controls; loading, empty, error and unavailable states;
escaped external text; stable identity selection event; cleanup on mount/unmount;
configurable tile service/attribution; no hard-coded promise of free production map tiles.
Host applications retain endpoint authorization, carrier eligibility and order persistence.

Core Composer installation must stay free of required frontend frameworks. Include a simple
Vue/React-compatible event contract, test it and document installation/build requirements.
Do not fetch the entire remote carrier feed per browser interaction. Do not implement
geocoding or arbitrary address search without a separately approved service policy.
