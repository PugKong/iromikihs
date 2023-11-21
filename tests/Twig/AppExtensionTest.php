<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Tests\Trait\GetService;
use App\Twig\AppExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFilter;

final class AppExtensionTest extends KernelTestCase
{
    use GetService;

    public function testGetFilters(): void
    {
        $extension = self::getService(AppExtension::class);
        $filterNames = array_map(fn (TwigFilter $f) => $f->getName(), $extension->getFilters());

        self::assertSame(['shikimori_url'], $filterNames);
    }

    public function testShikimoriUrl(): void
    {
        $extension = self::getService(AppExtension::class);
        $filter = array_reduce(
            $extension->getFilters(),
            fn (?TwigFilter $carry, TwigFilter $item) => 'shikimori_url' === $item->getName() ? $item : null,
        );

        self::assertInstanceOf(TwigFilter::class, $filter);
        $callable = $filter->getCallable();
        self::assertIsCallable($callable);
        self::assertSame('https://shikimori.example.com/test', ($callable)('/test'));
    }
}
