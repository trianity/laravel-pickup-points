<?php

declare(strict_types=1);

namespace Trianity\PickupPoints\Actions;

use DateTimeImmutable;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\LockProvider;
use Trianity\PickupPoints\Contracts\CatalogRepository;
use Trianity\PickupPoints\Data\CatalogSnapshot;
use Trianity\PickupPoints\Exceptions\CatalogException;
use Trianity\PickupPoints\ProviderRegistry;
use Trianity\PickupPoints\Support\Scope;

final class SyncCatalog
{
    public function __construct(
        private readonly ProviderRegistry $providers,
        private readonly CatalogRepository $repository,
        private readonly Factory $cache,
        private readonly ?string $lockStore = null,
        private readonly int $lockSeconds = 120,
    ) {}

    public function execute(string $provider, string $country = 'HU'): CatalogSnapshot
    {
        $country = Scope::country($country);
        $adapter = $this->providers->get($provider, $country);
        $store = $this->cache->store($this->lockStore)->getStore();
        if (! $store instanceof LockProvider || $this->lockSeconds < 1) {
            throw new CatalogException('Catalog synchronization needs an atomic-lock cache store and a positive lock lifetime.');
        }
        $lock = $store->lock('pickup-points:sync:'.$provider.':'.$country, $this->lockSeconds);
        if (! $lock->get()) {
            throw new CatalogException('Catalog synchronization is already running for '.$provider.'/'.$country.'.');
        }
        try {
            $snapshot = new CatalogSnapshot($provider, $country, new DateTimeImmutable, $adapter->fetch($country));
            $this->repository->replace($snapshot);

            return $snapshot;
        } finally {
            $lock->release();
        }
    }
}
