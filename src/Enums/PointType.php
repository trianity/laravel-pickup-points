<?php

declare(strict_types=1);

namespace Trianity\PickupPoints\Enums;

enum PointType: string
{
    case Shop = 'shop';
    case Locker = 'locker';
}
