<?php

declare(strict_types=1);

namespace App\Entity;

enum UserSeriesState: string
{
    case COMPLETE = 'complete';
    case INCOMPLETE = 'incomplete';
}
