<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Tests\Trait\GetService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

abstract class ServiceTestCase extends KernelTestCase
{
    use Factories;
    use GetService;
}
