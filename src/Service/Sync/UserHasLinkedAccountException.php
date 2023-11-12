<?php

declare(strict_types=1);

namespace App\Service\Sync;

use App\Entity\User;
use RuntimeException;

final class UserHasLinkedAccountException extends RuntimeException
{
    public static function create(User $user): self
    {
        return new self("User '{$user->getId()}' already has linked account");
    }
}
