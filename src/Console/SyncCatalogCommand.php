<?php

declare(strict_types=1);

namespace Trianity\PickupPoints\Console;

use Illuminate\Console\Command;
use Throwable;
use Trianity\PickupPoints\Actions\SyncCatalog;

final class SyncCatalogCommand extends Command
{
    protected $signature = 'pickup-points:sync {provider=gls} {--country=HU}';

    protected $description = 'Download and atomically replace a pickup-point catalog';

    public function handle(SyncCatalog $sync): int
    {
        try {
            $provider = $this->argument('provider');
            $country = $this->option('country');
            if (! is_string($provider) || ! is_string($country)) {
                throw new \InvalidArgumentException('Provider and country must be strings.');
            }
            $snapshot = $sync->execute($provider, $country);
            $this->info(sprintf('Synchronized %d points for %s/%s.', count($snapshot->points), $snapshot->provider, $snapshot->country));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
