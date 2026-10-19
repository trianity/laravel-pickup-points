# Task 04 — Optional Livewire 4 bridge

Depends on the picker event contract. Design the packaging so Livewire is installed only by
consumers who opt in. Core installation and CI must continue working without Livewire.

Use TDD to implement a Livewire 4 bridge that selects provider/country/point identity,
resolves canonical data on the server, exposes a clear event/value contract to the host,
and handles rerenders/navigation without duplicate maps/listeners. Do not bind to a host
Order, User, Contact or checkout model. UI state is not authorization.

Test tampered identities, unsupported scopes, cleared selection and navigation lifecycle.
Add an isolated consumer example and documented optional dependency/install steps.
Do not impose Flux Pro or any other paid UI dependency.
