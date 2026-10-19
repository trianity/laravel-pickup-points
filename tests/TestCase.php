<?php

declare(strict_types=1);

namespace Trianity\PickupPoints\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use Trianity\PickupPoints\PickupPointsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected string $catalogDirectory;

    protected function getPackageProviders($app): array
    {
        return [PickupPointsServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalogDirectory = sys_get_temp_dir().'/pickup-points-tests-'.bin2hex(random_bytes(8));
        config(['pickup-points.storage_path' => $this->catalogDirectory, 'cache.default' => 'array']);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->catalogDirectory);
        parent::tearDown();
    }
}
