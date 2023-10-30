<?php

declare(strict_types=1);

namespace App\Validator\User;

use App\Repository\UserRepository;
use ReflectionProperty;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function is_object;
use function is_string;

final class UsernameUniqueValidator extends ConstraintValidator
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        if (!$constraint instanceof UsernameUnique) {
            throw new UnexpectedTypeException($constraint, UsernameUnique::class);
        }

        $value = $this->getUsernameValue($value, $constraint);
        if (null === $value || '' === $value) {
            return;
        }

        if (!$this->users->isUsernameOccupied($value)) {
            return;
        }

        $this->context
            ->buildViolation('Username "{{ username }}" is already in use')
            ->setParameter('{{ username }}', $value)
            ->addViolation()
        ;
    }

    private function getUsernameValue(object $object, UsernameUnique $constraint): ?string
    {
        $reflection = new ReflectionProperty($object, $constraint->usernamePropertyName);
        $value = $reflection->getValue($object);
        if (null === $value || is_string($value)) {
            return $value;
        }

        throw new UnexpectedTypeException($value, 'string');
    }
}
