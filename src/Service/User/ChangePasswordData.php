<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;
use App\Validator\Constraint\PasswordRequirements;

final class ChangePasswordData
{
    public function __construct(
        public User $user,
        #[PasswordRequirements]
        public string $password = '',
    ) {
    }
}
