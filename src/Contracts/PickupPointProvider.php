<?php

declare(strict_types=1);

namespace Trianity\PickupPoints\Contracts;

use Trianity\PickupPoints\Data\PickupPoint;

interface PickupPointProvider
{
    public function id(): string;

    /** @return list<string> Uppercase ISO country codes. */
    public function countries(): array;

    /** @return list<PickupPoint> Throw on incomplete or invalid responses. */
    public function fetch(string $country): array;
}
