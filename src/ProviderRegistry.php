<?php

declare(strict_types=1);

namespace Trianity\PickupPoints;

use InvalidArgumentException;
use Trianity\PickupPoints\Contracts\PickupPointProvider;
use Trianity\PickupPoints\Support\Scope;

final class ProviderRegistry
{
    /** @var array<string, PickupPointProvider> */
    private array $providers = [];

    public function register(PickupPointProvider $provider): void
    {
        $id = Scope::provider($provider->id());
        if (isset($this->providers[$id])) {
            throw new InvalidArgumentException('Provider already registered: '.$id);
        }
        foreach ($provider->countries() as $country) {
            if (Scope::country($country) !== $country) {
                throw new InvalidArgumentException('Provider countries must be uppercase.');
            }
        }
        $this->providers[$id] = $provider;
    }

    public function get(string $id, string $country): PickupPointProvider
    {
        Scope::provider($id);
        $country = Scope::country($country);
        $provider = $this->providers[$id] ?? throw new InvalidArgumentException('Unknown provider: '.$id);
        if (! in_array($country, $provider->countries(), true)) {
            throw new InvalidArgumentException('Unsupported country for '.$id.': '.$country);
        }

        return $provider;
    }
}
