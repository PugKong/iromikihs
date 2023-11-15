<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Entity\User;
use RuntimeException;

final class UserHasSyncInProgressException extends RuntimeException
{
    public static function create(User $user): self
    {
        return new self("User '{$user->getId()}' has sync in progress");
    }
}
