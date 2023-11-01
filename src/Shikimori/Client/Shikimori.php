<?php

declare(strict_types=1);

namespace App\Shikimori\Client;

interface Shikimori
{
    /**
     * @phpstan-template T of object
     *
     * @phpstan-param Request<T> $request
     *
     * @phpstan-return ($request is ListRequest ? T[] : T)
     */
    public function request(Request $request): object|array;
}
