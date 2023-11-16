<?php

declare(strict_types=1);

namespace App\Entity;

use App\Shikimori\Api\Enum\UserAnimeStatus;

enum AnimeRateStatus: string
{
    case PLANNED = 'planned';
    case WATCHING = 'watching';
    case REWATCHING = 'rewatching';
    case COMPLETED = 'completed';
    case ON_HOLD = 'on_hold';
    case DROPPED = 'dropped';
    case SKIPPED = 'skipped';

    public static function fromUserAnimeStatus(UserAnimeStatus $status): self
    {
        return self::from($status->value);
    }
}
