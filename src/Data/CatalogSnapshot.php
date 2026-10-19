<?php

declare(strict_types=1);

namespace Trianity\PickupPoints\Data;

use DateTimeImmutable;
use InvalidArgumentException;
use Trianity\PickupPoints\Support\Scope;

final readonly class CatalogSnapshot
{
    /** @param list<PickupPoint> $points */
    public function __construct(
        public string $provider,
        public string $country,
        public DateTimeImmutable $syncedAt,
        public array $points,
    ) {
        Scope::provider($provider);
        if (Scope::country($country) !== $country || $points === []) {
            throw new InvalidArgumentException('Invalid catalog scope or empty point list.');
        }
        $seen = [];
        foreach ($points as $point) {
            if ($point->provider !== $provider || $point->country !== $country || isset($seen[$point->id])) {
                throw new InvalidArgumentException('Duplicate point or inconsistent catalog scope.');
            }
            $seen[$point->id] = true;
        }
    }
}
