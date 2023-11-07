<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Tests\Trait\GetService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

abstract class RepositoryTestCase extends KernelTestCase
{
    use Factories;
    use GetService;
}
