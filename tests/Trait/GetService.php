<?php

declare(strict_types=1);

namespace App\Tests\Trait;

use Symfony\Component\DependencyInjection\ContainerInterface;

trait GetService
{
    abstract protected static function getContainer(): ContainerInterface;

    /**
     * @phpstan-template T of object
     *
     * @phpstan-param  class-string<T> $name
     *
     * @phpstan-return T
     */
    protected static function getService(string $name): object
    {
        // @phpstan-ignore-next-line
        return self::getContainer()->get($name);
    }
}
