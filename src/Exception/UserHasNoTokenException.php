<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\User;
use RuntimeException;

final class UserHasNoTokenException extends RuntimeException
{
    public static function create(User $user): self
    {
        return new self(sprintf('User %s has no token', $user->getId()));
    }
}
