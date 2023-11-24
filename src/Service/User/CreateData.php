<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Validator\Constraint\PasswordRequirements;
use App\Validator\User\UsernameUnique;
use Symfony\Component\Validator\Constraints\Length;

#[UsernameUnique(groups: [self::USERNAME_GROUP])]
final class CreateData
{
    public const USERNAME_GROUP = 'username';
    public const PASSWORD_GROUP = 'password';

    public function __construct(
        #[Length(
            min: 3,
            max: 180,
            minMessage: 'Username is too short. It should have {{ limit }} character(s) or more.',
            maxMessage: 'Username is too long. It should have {{ limit }} character(s) or less.',
            groups: [self::USERNAME_GROUP],
        )]
        public string $username = '',
        #[PasswordRequirements(['groups' => [self::PASSWORD_GROUP]])]
        public string $password = '',
    ) {
    }
}
