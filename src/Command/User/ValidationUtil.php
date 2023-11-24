<?php

declare(strict_types=1);

namespace App\Command\User;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait ValidationUtil
{
    private function printValidationErrors(SymfonyStyle $io, ConstraintViolationListInterface $violations): void
    {
        foreach ($violations as $violation) {
            $io->error((string) $violation->getMessage());
        }
    }
}
