<?php

declare(strict_types=1);

namespace Trianity\PickupPoints\Providers;

use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use JsonException;
use Trianity\PickupPoints\Contracts\PickupPointProvider;
use Trianity\PickupPoints\Data\PickupPoint;
use Trianity\PickupPoints\Enums\PointType;
use Trianity\PickupPoints\Exceptions\CatalogException;
use Trianity\PickupPoints\Support\Scope;

final class GlsProvider implements PickupPointProvider
{
    /** @param list<string> $countries */
    public function __construct(
        private readonly Factory $http,
        private readonly array $countries = ['HU'],
        private readonly string $baseUrl = 'https://map.gls-hungary.com/data/deliveryPoints',
        private readonly int $connectTimeout = 5,
        private readonly int $timeout = 15,
        private readonly int $maxBytes = 10485760,
    ) {
        if (parse_url($baseUrl, PHP_URL_SCHEME) !== 'https' || $connectTimeout < 1 || $timeout < 1 || $maxBytes < 1) {
            throw new InvalidArgumentException('GLS requires HTTPS and positive request limits.');
        }
    }

    public function id(): string
    {
        return 'gls';
    }

    public function countries(): array
    {
        return $this->countries;
    }

    public function fetch(string $country): array
    {
        $country = Scope::country($country);
        if (! in_array($country, $this->countries, true)) {
            throw new InvalidArgumentException('Unsupported GLS country: '.$country);
        }
        $response = $this->http->acceptJson()->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)->withoutRedirecting()
            ->get(rtrim($this->baseUrl, '/').'/'.strtolower($country).'.json');

        if (! $response->successful()) {
            throw new CatalogException('GLS catalog request failed with HTTP '.$response->status().'.');
        }
        $body = $response->body();
        if (strlen($body) > $this->maxBytes) {
            throw new CatalogException('GLS catalog exceeds the configured response size limit.');
        }
        try {
            $data = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new CatalogException('Invalid GLS JSON response.', previous: $e);
        }
        if (! is_array($data) || ! isset($data['items']) || ! is_array($data['items']) || ! array_is_list($data['items']) || $data['items'] === []) {
            throw new CatalogException('GLS response must contain a non-empty items list.');
        }

        $points = [];
        foreach ($data['items'] as $item) {
            if (! is_array($item)) {
                throw new CatalogException('Invalid GLS point entry.');
            }
            $points[] = $this->normalize($item, $country);
        }

        return $points;
    }

    /** @param array<array-key, mixed> $item */
    private function normalize(array $item, string $country): PickupPoint
    {
        $contact = $item['contact'] ?? null;
        $location = $item['location'] ?? null;
        if (! is_array($contact) || ! is_array($location) || ! isset($location[0], $location[1])) {
            throw new CatalogException('GLS point is missing its address or coordinates.');
        }
        $type = match ($item['type'] ?? null) {
            'parcel-shop' => PointType::Shop,
            'parcel-locker' => PointType::Locker,
            default => throw new CatalogException('Unknown GLS point type.'),
        };
        $metadata = array_intersect_key($item, array_flip([
            'hours', 'description', 'holiday', 'lockerSaturation', 'hasWheelchairAccess',
        ]));

        try {
            $point = PickupPoint::fromArray([
                'provider' => 'gls', 'id' => $item['id'] ?? null,
                'country' => $contact['countryCode'] ?? null, 'type' => $type->value,
                'name' => $item['name'] ?? null, 'postal_code' => $contact['postalCode'] ?? null,
                'city' => $contact['city'] ?? null, 'address' => $contact['address'] ?? null,
                'latitude' => $location[0], 'longitude' => $location[1],
                'features' => $item['features'] ?? [], 'metadata' => $metadata,
            ]);
        } catch (InvalidArgumentException $e) {
            throw new CatalogException('Invalid GLS point data.', previous: $e);
        }
        if ($point->country !== $country) {
            throw new CatalogException('GLS point country does not match the requested catalog.');
        }

        return $point;
    }
}
