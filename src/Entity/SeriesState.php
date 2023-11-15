<?php

declare(strict_types=1);

namespace App\Entity;

enum SeriesState: string
{
    case COMPLETE = 'complete';
    case INCOMPLETE = 'incomplete';
    case DROPPED = 'dropped';
}
