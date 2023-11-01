<?php

declare(strict_types=1);

namespace App\Shikimori\Client;

/**
 * @phpstan-template T of object
 */
interface Request
{
    public function method(): string;

    public function uri(): string;

    /**
     * @phpstan-return class-string<T>
     */
    public function responseClass(): string;
}
