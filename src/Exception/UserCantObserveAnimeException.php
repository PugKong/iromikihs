<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\Anime;
use App\Entity\User;
use RuntimeException;

final class UserCantObserveAnimeException extends RuntimeException
{
    public static function create(User $user, Anime $anime): self
    {
        return new self(sprintf(
            'Can not observe anime %d for user %s, because it is was not skipped',
            $anime->getId(),
            $user->getId(),
        ));
    }
}
