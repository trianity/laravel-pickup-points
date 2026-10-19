<?php

declare(strict_types=1);

use Trianity\PickupPoints\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/** @return array<string, mixed> */
function glsFixture(): array
{
    return json_decode(file_get_contents(__DIR__.'/Fixtures/gls-hu.json'), true, 512, JSON_THROW_ON_ERROR);
}
