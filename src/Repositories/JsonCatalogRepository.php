<?php

declare(strict_types=1);

namespace Trianity\PickupPoints\Repositories;

use DateTimeImmutable;
use Illuminate\Filesystem\Filesystem;
use Throwable;
use Trianity\PickupPoints\Contracts\CatalogRepository;
use Trianity\PickupPoints\Data\CatalogSnapshot;
use Trianity\PickupPoints\Data\PickupPoint;
use Trianity\PickupPoints\Exceptions\CatalogException;
use Trianity\PickupPoints\Support\Scope;

final class JsonCatalogRepository implements CatalogRepository
{
    public function __construct(private readonly Filesystem $files, private readonly string $directory) {}

    public function get(string $provider, string $country): ?CatalogSnapshot
    {
        $country = Scope::country($country);
        $path = $this->path($provider, $country);
        if (! $this->files->exists($path)) {
            return null;
        }
        try {
            $data = json_decode($this->files->get($path), true, 64, JSON_THROW_ON_ERROR);
            if (! is_array($data) || ($data['schema_version'] ?? null) !== 1
                || ($data['provider'] ?? null) !== $provider || ($data['country'] ?? null) !== $country
                || ! is_string($data['synced_at'] ?? null) || ! is_array($data['points'] ?? null)
                || ! array_is_list($data['points'])) {
                throw new CatalogException('Invalid catalog envelope.');
            }
            $points = [];
            foreach ($data['points'] as $point) {
                if (! is_array($point)) {
                    throw new CatalogException('Invalid catalog point.');
                }
                /** @var array<string, mixed> $point */
                $points[] = PickupPoint::fromArray($point);
            }

            return new CatalogSnapshot($provider, $country, new DateTimeImmutable($data['synced_at']), $points);
        } catch (Throwable $e) {
            throw new CatalogException('Cannot read catalog '.$provider.'/'.$country.'.', previous: $e);
        }
    }

    public function replace(CatalogSnapshot $snapshot): void
    {
        $path = $this->path($snapshot->provider, $snapshot->country);
        try {
            $json = json_encode([
                'schema_version' => 1,
                'provider' => $snapshot->provider,
                'country' => $snapshot->country,
                'synced_at' => $snapshot->syncedAt->format(DATE_ATOM),
                'points' => $snapshot->points,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->files->ensureDirectoryExists($this->directory);
            $this->publish($path, $json);
        } catch (Throwable $e) {
            throw new CatalogException('Cannot replace catalog '.$snapshot->provider.'/'.$snapshot->country.'.', previous: $e);
        }
    }

    private function publish(string $path, string $json): void
    {
        $temporary = tempnam($this->directory, '.pickup-');
        if ($temporary === false) {
            throw new CatalogException('Cannot create a catalog staging file.');
        }
        try {
            if (! chmod($temporary, 0600) || file_put_contents($temporary, $json) !== strlen($json)) {
                throw new CatalogException('Incomplete catalog staging write.');
            }
            if (! rename($temporary, $path)) {
                throw new CatalogException('Cannot atomically publish the catalog.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    private function path(string $provider, string $country): string
    {
        return rtrim($this->directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR
            .Scope::provider($provider).'-'.Scope::country($country).'.json';
    }
}
