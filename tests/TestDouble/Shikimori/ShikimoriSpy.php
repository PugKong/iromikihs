<?php

declare(strict_types=1);

namespace App\Tests\TestDouble\Shikimori;

use App\Shikimori\Client\ListRequest;
use App\Shikimori\Client\Request;
use App\Shikimori\Client\Shikimori;
use PHPUnit\Framework\Assert;
use RuntimeException;

final class ShikimoriSpy implements Shikimori
{
    /**
     * @var array<array{0: Request<object>, 1: object|array<object>}>
     */
    private array $requestResponsePairs = [];
    /**
     * @var Request<object>[]
     */
    private array $requests = [];

    /**
     * @phpstan-template T of object
     *
     * @phpstan-param Request<T> $request
     * @phpstan-param ($request is ListRequest ? T[] : T) $response
     */
    public function addRequest(Request $request, object|array $response): void
    {
        $this->requestResponsePairs[] = [$request, $response];
    }

    public function request(Request $request): object|array
    {
        $this->requests[] = $request;

        foreach ($this->requestResponsePairs as $pair) {
            if ($request != $pair[0]) {
                continue;
            }

            // @phpstan-ignore-next-line
            return $pair[1];
        }

        throw new RuntimeException(sprintf('Oh no, request %s not found', $request::class));
    }

    public function assertCalls(int $expected): void
    {
        Assert::assertCount($expected, $this->requests);
    }
}
