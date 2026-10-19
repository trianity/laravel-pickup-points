<?php

declare(strict_types=1);

namespace Trianity\PickupPoints;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Trianity\PickupPoints\Actions\SyncCatalog;
use Trianity\PickupPoints\Console\SyncCatalogCommand;
use Trianity\PickupPoints\Contracts\CatalogRepository;
use Trianity\PickupPoints\Contracts\PickupPointProvider;
use Trianity\PickupPoints\Providers\GlsProvider;
use Trianity\PickupPoints\Repositories\JsonCatalogRepository;

final class PickupPointsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pickup-points.php', 'pickup-points');

        $this->app->singleton(GlsProvider::class, fn ($app) => new GlsProvider(
            $app->make(HttpFactory::class),
            config('pickup-points.gls.countries', ['HU']),
            config('pickup-points.gls.base_url'),
            config('pickup-points.gls.connect_timeout', 5),
            config('pickup-points.gls.timeout', 15),
            config('pickup-points.gls.max_bytes', 10485760),
        ));
        $this->app->singleton(ProviderRegistry::class, function ($app): ProviderRegistry {
            $registry = new ProviderRegistry;
            foreach (config('pickup-points.providers', []) as $id => $class) {
                $provider = $app->make($class);
                if (! $provider instanceof PickupPointProvider || $provider->id() !== $id) {
                    throw new InvalidArgumentException('Provider configuration key must match the adapter id.');
                }
                $registry->register($provider);
            }

            return $registry;
        });
        $this->app->singleton(CatalogRepository::class, fn ($app) => new JsonCatalogRepository(
            $app->make(Filesystem::class), config('pickup-points.storage_path'),
        ));
        $this->app->singleton(PickupPoints::class);
        $this->app->singleton(SyncCatalog::class, fn ($app) => new SyncCatalog(
            $app->make(ProviderRegistry::class), $app->make(CatalogRepository::class),
            $app->make(CacheFactory::class), config('pickup-points.lock_store'),
            config('pickup-points.lock_seconds', 120),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/pickup-points.php' => config_path('pickup-points.php')], 'pickup-points-config');
            $this->commands([SyncCatalogCommand::class]);
        }
    }
}
