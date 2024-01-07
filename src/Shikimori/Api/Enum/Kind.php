<?php

declare(strict_types=1);

namespace App\Shikimori\Api\Enum;

enum Kind: string
{
    case TV = 'tv';
    case MOVIE = 'movie';
    case OVA = 'ova';
    case ONA = 'ona';
    case SPECIAL = 'special';
    case TV_SPECIAL = 'tv_special';
    case MUSIC = 'music';
    case TV_13 = 'tv_13';
    case TV_24 = 'tv_24';
    case TV_48 = 'tv_48';
    case PV = 'pv';
    case CM = 'cm';
}
