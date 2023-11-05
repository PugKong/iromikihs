<?php

declare(strict_types=1);

namespace App\Shikimori\Api\Enum;

enum UserAnimeStatus: string
{
    case PLANNED = 'planned';
    case WATCHING = 'watching';
    case REWATCHING = 'rewatching';
    case COMPLETED = 'completed';
    case ON_HOLD = 'on_hold';
    case DROPPED = 'dropped';
}
