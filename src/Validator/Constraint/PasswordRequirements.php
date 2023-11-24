<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\PasswordStrength;

#[Attribute]
final class PasswordRequirements extends Compound
{
    /**
     * @param array<string, mixed> $options
     */
    protected function getConstraints(array $options): array
    {
        return [
            new PasswordStrength(minScore: PasswordStrength::STRENGTH_STRONG),
        ];
    }
}
