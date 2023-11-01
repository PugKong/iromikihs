<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Tests\Trait\GetService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

abstract class MessageHandlerTestCase extends KernelTestCase
{
    use Factories;
    use GetService;
}
