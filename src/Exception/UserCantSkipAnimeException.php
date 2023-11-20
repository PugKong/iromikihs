<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\Anime;
use App\Entity\User;
use RuntimeException;

final class UserCantSkipAnimeException extends RuntimeException
{
    public static function create(User $user, Anime $anime): self
    {
        return new self(sprintf(
            'Can not skip anime %d for user %s, because it is already rated',
            $anime->getId(),
            $user->getId(),
        ));
    }
}
