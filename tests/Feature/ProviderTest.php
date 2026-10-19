<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Trianity\PickupPoints\Contracts\PickupPointProvider;
use Trianity\PickupPoints\Exceptions\CatalogException;
use Trianity\PickupPoints\ProviderRegistry;
use Trianity\PickupPoints\Providers\GlsProvider;

it('registers a third-party adapter without changing the GLS implementation', function () {
    $adapter = new class implements PickupPointProvider
    {
        public function id(): string
        {
            return 'example';
        }

        public function countries(): array
        {
            return ['HU'];
        }

        public function fetch(string $country): array
        {
            return [];
        }
    };
    $registry = app(ProviderRegistry::class);
    $registry->register($adapter);
    expect($registry->get('example', 'hu'))->toBe($adapter);
    expect(fn () => $registry->register($adapter))->toThrow(InvalidArgumentException::class);
});

it('resolves adapters registered through configuration', function () {
    config(['pickup-points.providers' => ['gls' => GlsProvider::class]]);
    expect(app(ProviderRegistry::class)->get('gls', 'HU'))->toBeInstanceOf(GlsProvider::class);
});

it('rejects adapter configuration whose key does not match the provider id', function () {
    config(['pickup-points.providers' => ['wrong' => GlsProvider::class]]);
    expect(fn () => app(ProviderRegistry::class))->toThrow(InvalidArgumentException::class);
});

it('rejects oversized responses', function () {
    Http::fake(['*' => Http::response(glsFixture())]);
    $adapter = new GlsProvider(app(Factory::class), maxBytes: 10);
    expect(fn () => $adapter->fetch('HU'))->toThrow(CatalogException::class, 'size limit');
});

it('does not follow catalog redirects', function () {
    Http::fake(['*' => Http::response('', 302, ['Location' => 'http://localhost/internal'])]);
    expect(fn () => app(GlsProvider::class)->fetch('HU'))->toThrow(CatalogException::class, 'HTTP 302');
    Http::assertSentCount(1);
});

it('requires HTTPS for the deployment-configured GLS endpoint', function () {
    new GlsProvider(app(Factory::class), baseUrl: 'http://localhost');
})->throws(InvalidArgumentException::class);
