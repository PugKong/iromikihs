<?php

declare(strict_types=1);

namespace App\Shikimori\Api\Enum;

enum Status: string
{
    case ANONS = 'anons';
    case ONGOING = 'ongoing';
    case RELEASED = 'released';
}
