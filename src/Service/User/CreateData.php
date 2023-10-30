<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Validator\User\UsernameUnique;
use Symfony\Component\Validator\Constraints as Assert;

#[UsernameUnique(groups: [self::USERNAME_GROUP])]
final class CreateData
{
    public const USERNAME_GROUP = 'username';
    public const PASSWORD_GROUP = 'password';

    #[Assert\Length(
        min: 3,
        max: 180,
        minMessage: 'Username is too short. It should have {{ limit }} character(s) or more.',
        maxMessage: 'Username is too long. It should have {{ limit }} character(s) or less.',
        groups: [self::USERNAME_GROUP],
    )]
    public string $username = '';

    #[Assert\PasswordStrength(
        minScore: Assert\PasswordStrength::STRENGTH_STRONG,
        groups: [self::PASSWORD_GROUP],
    )]
    public string $password = '';
}
