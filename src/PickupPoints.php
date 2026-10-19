<?php

declare(strict_types=1);

namespace Trianity\PickupPoints;

use Trianity\PickupPoints\Contracts\CatalogRepository;
use Trianity\PickupPoints\Data\CatalogSnapshot;
use Trianity\PickupPoints\Data\PickupPoint;
use Trianity\PickupPoints\Enums\PointType;
use Trianity\PickupPoints\Exceptions\CatalogException;
use Trianity\PickupPoints\Support\Scope;

final class PickupPoints
{
    public function __construct(private readonly ProviderRegistry $providers, private readonly CatalogRepository $repository) {}

    public function snapshot(string $provider, string $country = 'HU'): CatalogSnapshot
    {
        $country = Scope::country($country);
        $this->providers->get($provider, $country);

        return $this->repository->get($provider, $country)
            ?? throw new CatalogException('No catalog available for '.$provider.'/'.$country.'. Run pickup-points:sync first.');
    }

    public function find(string $provider, string $id, string $country = 'HU'): ?PickupPoint
    {
        foreach ($this->snapshot($provider, $country)->points as $point) {
            if ($point->id === $id) {
                return $point;
            }
        }

        return null;
    }

    /** @return list<PickupPoint> Text search across ID, name, postcode, city and address. */
    public function search(string $provider, string $country = 'HU', string $query = '', ?PointType $type = null, int $limit = 50): array
    {
        if ($limit < 1 || $limit > 500 || mb_strlen($query) > 200) {
            throw new \InvalidArgumentException('Limit must be 1–500 and query at most 200 characters.');
        }
        $query = mb_strtolower(trim($query));
        $results = [];
        foreach ($this->snapshot($provider, $country)->points as $point) {
            if ($type !== null && $point->type !== $type) {
                continue;
            }
            $haystack = mb_strtolower(implode(' ', [$point->id, $point->name, $point->postalCode, $point->city, $point->address]));
            if ($query !== '' && ! str_contains($haystack, $query)) {
                continue;
            }
            $results[] = $point;
            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }
}
