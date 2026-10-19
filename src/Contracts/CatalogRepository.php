<?php

declare(strict_types=1);

namespace Trianity\PickupPoints\Contracts;

use Trianity\PickupPoints\Data\CatalogSnapshot;

interface CatalogRepository
{
    // Null only when absent; corrupt or unreadable catalogs must throw.
    public function get(string $provider, string $country): ?CatalogSnapshot;

    // Replace the entire scope atomically, retaining the previous snapshot on failure.
    public function replace(CatalogSnapshot $snapshot): void;
}
