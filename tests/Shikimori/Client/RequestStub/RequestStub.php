<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Client\RequestStub;

use App\Shikimori\Client\Request;

/**
 * @implements Request<ResponseStub>
 */
readonly class RequestStub implements Request
{
    public function __construct(private string $method, private string $uri)
    {
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function responseClass(): string
    {
        return ResponseStub::class;
    }
}
