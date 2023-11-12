<?php

declare(strict_types=1);

namespace App\Entity;

enum UserSyncState: string
{
    case LINK_ACCOUNT = 'link_account';
    case ANIME_RATES = 'anime_rates';
    case SERIES = 'series';
    case SERIES_RATES = 'series_rates';
    case FAILED = 'failed';
}
