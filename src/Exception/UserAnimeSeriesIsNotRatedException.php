<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\Series;
use App\Entity\User;
use RuntimeException;

final class UserAnimeSeriesIsNotRatedException extends RuntimeException
{
    public static function create(User $user, Series $series): self
    {
        return new self(sprintf(
            'Series %s was not rated by user %s',
            $series->getId(),
            $user->getId(),
        ));
    }
}
