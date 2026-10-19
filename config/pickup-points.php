<?php

declare(strict_types=1);

use Trianity\PickupPoints\Providers\GlsProvider;

return [
    // Private local storage, shared between CLI and web processes.
    'storage_path' => storage_path('app/pickup-points'),

    // Any configured Laravel cache store supporting atomic locks.
    'lock_store' => null,
    'lock_seconds' => 120,

    // Key must match the adapter's id(). Classes are resolved by the container.
    'providers' => [
        'gls' => GlsProvider::class,
    ],

    'gls' => [
        'countries' => ['HU'],
        // Trusted deployment configuration, never accept this from requests.
        'base_url' => 'https://map.gls-hungary.com/data/deliveryPoints',
        'connect_timeout' => 5,
        'timeout' => 15,
        'max_bytes' => 10 * 1024 * 1024,
    ],
];
