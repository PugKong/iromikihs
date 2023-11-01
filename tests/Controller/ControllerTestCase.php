<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\Trait\GetService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

abstract class ControllerTestCase extends WebTestCase
{
    use Factories;
    use GetService;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();
    }
}
