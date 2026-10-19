<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Trianity\PickupPoints\Actions\SyncCatalog;
use Trianity\PickupPoints\Contracts\CatalogRepository;
use Trianity\PickupPoints\Enums\PointType;
use Trianity\PickupPoints\Exceptions\CatalogException;
use Trianity\PickupPoints\PickupPoints;
use Trianity\PickupPoints\Repositories\JsonCatalogRepository;

function syncFixture(): void
{
    Http::fake(['*' => Http::response(glsFixture())]);
    app(SyncCatalog::class)->execute('gls');
}

it('synchronizes GLS and exposes normalized serializable points without a UI dependency', function () {
    syncFixture();
    $catalog = app(PickupPoints::class);
    $point = $catalog->find('gls', 'TEST-SHOP-001');

    expect($catalog->snapshot('gls')->points)->toHaveCount(2)
        ->and($point->type)->toBe(PointType::Shop)
        ->and($point->latitude)->toBe(47.48)
        ->and($point->metadata['holiday']['start'])->toBe('2026-12-24')
        ->and($point->metadata['lockerSaturation'])->toBe('outOfOrder')
        ->and($point->jsonSerialize()['postal_code'])->toBe('1111');

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://map.gls-hungary.com/data/deliveryPoints/hu.json');
    expect($catalog->find('gls', 'missing'))->toBeNull();
});

it('performs local Unicode text search and type filtering with bounded results', function () {
    syncFixture();
    $catalog = app(PickupPoints::class);

    expect($catalog->search('gls', 'hu', 'ÁRVÍZTŰRŐ'))->toHaveCount(1)
        ->and($catalog->search('gls', query: '4024')[0]->city)->toBe('Debrecen')
        ->and($catalog->search('gls', type: PointType::Locker)[0]->id)->toBe('TEST-LOCKER-002')
        ->and($catalog->search('gls', limit: 1))->toHaveCount(1);
    Http::assertSentCount(1);
});

it('rejects invalid search boundaries', function (int $limit) {
    app(PickupPoints::class)->search('gls', limit: $limit);
})->with([0, -1, 501])->throws(InvalidArgumentException::class);

it('distinguishes an absent catalog from an unknown point', function () {
    app(PickupPoints::class)->find('gls', 'missing');
})->throws(CatalogException::class, 'No catalog available');

it('preserves the previous complete catalog on a failed refresh', function (string $failure) {
    $data = glsFixture();
    match ($failure) {
        'empty' => $data['items'] = [],
        'duplicate' => $data['items'][] = $data['items'][0],
        'wrong-country' => $data['items'][1]['contact']['countryCode'] = 'SK',
        'bad-coordinates' => $data['items'][1]['location'] = [999, 21],
        'unknown-type' => $data['items'][1]['type'] = 'unexpected',
        'partial' => $data['items'][1]['contact'] = [],
        default => null,
    };
    $sequence = Http::fakeSequence()->push(glsFixture());
    match ($failure) {
        'http' => $sequence->push('unavailable', 503),
        'json' => $sequence->push('{broken'),
        'connection' => $sequence->pushFailedConnection(),
        default => $sequence->push($data),
    };
    app(SyncCatalog::class)->execute('gls');
    $before = file_get_contents($this->catalogDirectory.'/gls-HU.json');

    expect(fn () => app(SyncCatalog::class)->execute('gls'))->toThrow(Exception::class)
        ->and(file_get_contents($this->catalogDirectory.'/gls-HU.json'))->toBe($before)
        ->and(app(PickupPoints::class)->snapshot('gls')->points)->toHaveCount(2);
})->with(['http', 'json', 'connection', 'empty', 'duplicate', 'wrong-country', 'bad-coordinates', 'unknown-type', 'partial']);

it('refuses overlapping synchronization for the same provider and country', function () {
    $lock = Cache::lock('pickup-points:sync:gls:HU', 120);
    $lock->get();
    try {
        expect(fn () => app(SyncCatalog::class)->execute('gls'))->toThrow(CatalogException::class, 'already running');
        Http::assertNothingSent();
    } finally {
        $lock->release();
    }
});

it('releases the synchronization lock after failure', function () {
    Http::fakeSequence()->push([], 500)->push(glsFixture());
    expect(fn () => app(SyncCatalog::class)->execute('gls'))->toThrow(CatalogException::class);
    app(SyncCatalog::class)->execute('gls');
    expect(app(PickupPoints::class)->snapshot('gls')->points)->toHaveCount(2);
});

it('fails visibly on corrupt local catalogs', function () {
    syncFixture();
    file_put_contents($this->catalogDirectory.'/gls-HU.json', '{broken');
    expect(fn () => app(CatalogRepository::class)->get('gls', 'HU'))->toThrow(CatalogException::class, 'Cannot read');
});

it('rejects unsafe scope identifiers and unsupported countries before requesting data', function (string $provider, string $country) {
    expect(fn () => app(SyncCatalog::class)->execute($provider, $country))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
})->with([['../gls', 'HU'], ['gls', '../HU'], ['unknown', 'HU'], ['gls', 'SK']]);

it('uses the existing catalog during a remote outage without making read-time HTTP calls', function () {
    Http::fakeSequence()->push(glsFixture())->pushFailedConnection();
    app(SyncCatalog::class)->execute('gls');
    expect(app(PickupPoints::class)->search('gls'))->toHaveCount(2);
    Http::assertSentCount(1);
});

it('reports successful and failed synchronization through command exit codes', function () {
    Http::fakeSequence()->push(glsFixture())->push([], 503);
    $this->artisan('pickup-points:sync')->expectsOutput('Synchronized 2 points for gls/HU.')->assertSuccessful();
    $this->artisan('pickup-points:sync')->assertFailed();
});

it('isolates country catalogs even when their external point identifiers match', function () {
    config(['pickup-points.gls.countries' => ['HU', 'SK']]);
    $foreign = glsFixture();
    foreach ($foreign['items'] as &$item) {
        $item['contact']['countryCode'] = 'SK';
        $item['contact']['city'] = 'Example SK';
    }
    unset($item);
    Http::fakeSequence()->push(glsFixture())->push($foreign);
    app(SyncCatalog::class)->execute('gls', 'HU');
    app(SyncCatalog::class)->execute('gls', 'SK');

    expect(app(PickupPoints::class)->find('gls', 'TEST-SHOP-001', 'HU')->city)->toBe('Budapest')
        ->and(app(PickupPoints::class)->find('gls', 'TEST-SHOP-001', 'SK')->city)->toBe('Example SK');
});

it('keeps the old catalog readable while the next snapshot is downloading', function () {
    $replacement = glsFixture();
    $replacement['items'][0]['name'] = 'Updated shop';
    $requests = 0;
    Http::fake(function () use (&$requests, $replacement) {
        if (++$requests === 1) {
            return Http::response(glsFixture());
        }
        expect(app(PickupPoints::class)->find('gls', 'TEST-SHOP-001')->name)->toBe('Árvíztűrő mintabolt');

        return Http::response($replacement);
    });
    app(SyncCatalog::class)->execute('gls');
    app(SyncCatalog::class)->execute('gls');
    expect(app(PickupPoints::class)->find('gls', 'TEST-SHOP-001')->name)->toBe('Updated shop');
});

it('retains the old file when a repository storage operation fails', function () {
    syncFixture();
    $before = file_get_contents($this->catalogDirectory.'/gls-HU.json');
    $snapshot = app(PickupPoints::class)->snapshot('gls');
    $files = Mockery::mock(Filesystem::class);
    $files->shouldReceive('ensureDirectoryExists')->once()->andThrow(new RuntimeException('Storage unavailable'));
    $repository = new JsonCatalogRepository($files, $this->catalogDirectory);

    expect(fn () => $repository->replace($snapshot))->toThrow(CatalogException::class, 'Cannot replace')
        ->and(file_get_contents($this->catalogDirectory.'/gls-HU.json'))->toBe($before);
});
