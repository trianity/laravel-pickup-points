<?php

declare(strict_types=1);

use Trianity\PickupPoints\Data\CatalogSnapshot;
use Trianity\PickupPoints\Data\PickupPoint;
use Trianity\PickupPoints\Enums\PointType;

function pointFixture(): PickupPoint
{
    return new PickupPoint('gls', 'EXAMPLE-1', 'HU', PointType::Shop, 'Mintabolt', '1111', 'Budapest', 'Minta utca 1.', 47.5, 19.0);
}

it('round trips the immutable point through its public JSON format', function () {
    $point = pointFixture();
    expect(PickupPoint::fromArray($point->jsonSerialize()))->toEqual($point);
});

it('rejects duplicate identifiers in one provider-country catalog', function () {
    new CatalogSnapshot('gls', 'HU', new DateTimeImmutable, [pointFixture(), pointFixture()]);
})->throws(InvalidArgumentException::class);

it('rejects empty catalogs', function () {
    new CatalogSnapshot('gls', 'HU', new DateTimeImmutable, []);
})->throws(InvalidArgumentException::class);

it('rejects missing required point fields', function (string $field) {
    $data = pointFixture()->jsonSerialize();
    unset($data[$field]);
    PickupPoint::fromArray($data);
})->with(['id', 'country', 'name', 'latitude', 'longitude'])->throws(InvalidArgumentException::class);
