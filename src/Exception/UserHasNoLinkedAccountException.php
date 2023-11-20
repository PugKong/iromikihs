<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\User;
use RuntimeException;

final class UserHasNoLinkedAccountException extends RuntimeException
{
    public static function create(User $user): self
    {
        return new self("User '{$user->getId()}' has no linked account");
    }
}
