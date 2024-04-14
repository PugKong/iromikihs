<?php

declare(strict_types=1);

namespace App\Validator\User;

use Attribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
final class UsernameUnique extends Constraint
{
    #[HasNamedArguments]
    public function __construct(
        public string $usernamePropertyName = 'username',
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
