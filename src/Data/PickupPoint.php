<?php

declare(strict_types=1);

namespace Trianity\PickupPoints\Data;

use InvalidArgumentException;
use JsonSerializable;
use Trianity\PickupPoints\Enums\PointType;
use Trianity\PickupPoints\Support\Scope;

final readonly class PickupPoint implements JsonSerializable
{
    /**
     * @param  list<string>  $features  Provider features; absence does not mean unsupported.
     * @param  array<string, mixed>  $metadata  Provider-specific information, not checkout authorization.
     */
    public function __construct(
        public string $provider,
        public string $id,
        public string $country,
        public PointType $type,
        public string $name,
        public string $postalCode,
        public string $city,
        public string $address,
        public float $latitude,
        public float $longitude,
        public array $features = [],
        public array $metadata = [],
    ) {
        Scope::provider($provider);
        if (Scope::country($country) !== $country) {
            throw new InvalidArgumentException('Point country must be uppercase.');
        }
        foreach ([$id, $name, $postalCode, $city, $address] as $value) {
            if (trim($value) === '' || strlen($value) > 1000) {
                throw new InvalidArgumentException('Required point text is empty or too long.');
            }
        }
        if (! is_finite($latitude) || ! is_finite($longitude) || abs($latitude) > 90 || abs($longitude) > 180) {
            throw new InvalidArgumentException('Invalid point coordinates.');
        }
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'provider' => $this->provider, 'id' => $this->id,
            'country' => $this->country, 'type' => $this->type->value,
            'name' => $this->name, 'postal_code' => $this->postalCode,
            'city' => $this->city, 'address' => $this->address,
            'latitude' => $this->latitude, 'longitude' => $this->longitude,
            'features' => $this->features, 'metadata' => $this->metadata,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        foreach (['provider', 'id', 'country', 'type', 'name', 'postal_code', 'city', 'address'] as $key) {
            if (! isset($data[$key]) || ! is_string($data[$key])) {
                throw new InvalidArgumentException('Invalid point field: '.$key);
            }
        }
        foreach (['latitude', 'longitude'] as $key) {
            if (! isset($data[$key]) || ! is_numeric($data[$key])) {
                throw new InvalidArgumentException('Invalid coordinate: '.$key);
            }
        }
        $features = $data['features'] ?? [];
        $metadata = $data['metadata'] ?? [];
        if (! is_array($features) || ! array_is_list($features) || ! is_array($metadata)) {
            throw new InvalidArgumentException('Invalid point attributes.');
        }
        foreach ($features as $feature) {
            if (! is_string($feature)) {
                throw new InvalidArgumentException('Invalid feature.');
            }
        }
        foreach (array_keys($metadata) as $key) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('Invalid metadata key.');
            }
        }

        return new self(
            $data['provider'], $data['id'], $data['country'], PointType::from($data['type']),
            $data['name'], $data['postal_code'], $data['city'], $data['address'],
            (float) $data['latitude'], (float) $data['longitude'], $features, $metadata,
        );
    }
}
