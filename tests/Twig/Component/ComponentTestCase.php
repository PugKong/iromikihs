<?php

declare(strict_types=1);

namespace App\Tests\Twig\Component;

use App\Tests\Trait\GetService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;
use Zenstruck\Foundry\Test\Factories;

abstract class ComponentTestCase extends KernelTestCase
{
    use Factories;
    use GetService;
    use InteractsWithTwigComponents;
}
