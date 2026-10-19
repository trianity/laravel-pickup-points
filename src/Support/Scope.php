<?php

declare(strict_types=1);

namespace Trianity\PickupPoints\Support;

use InvalidArgumentException;

final class Scope
{
    public static function provider(string $provider): string
    {
        if (preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D', $provider) !== 1) {
            throw new InvalidArgumentException('Invalid provider identifier.');
        }

        return $provider;
    }

    public static function country(string $country): string
    {
        $country = strtoupper($country);

        if (preg_match('/\A[A-Z]{2}\z/D', $country) !== 1) {
            throw new InvalidArgumentException('Country must be a two-letter code.');
        }

        return $country;
    }
}
