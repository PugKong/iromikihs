<?php

declare(strict_types=1);

namespace App\Tests\TestDouble\Shikimori;

use App\Entity\User;
use App\Service\Shikimori\AnimeSeriesFetcher;
use App\Service\Shikimori\AnimeSeriesFetcherResult;
use PHPUnit\Framework\Assert;
use RuntimeException;

use function array_key_exists;

final class AnimeSeriesFetcherSpy implements AnimeSeriesFetcher
{
    /**
     * @var array<string, AnimeSeriesFetcherResult>
     */
    private array $results = [];
    /**
     * @var array{0: User, 1: int}[]
     */
    private array $calls = [];

    public function addResult(User $user, int $animeId, AnimeSeriesFetcherResult $result): void
    {
        $key = $this->key($user, $animeId);
        $this->results[$key] = $result;
    }

    public function __invoke(User $user, int $animeId): AnimeSeriesFetcherResult
    {
        $this->calls[] = [$user, $animeId];

        $key = $this->key($user, $animeId);
        if (array_key_exists($key, $this->results)) {
            return $this->results[$key];
        }

        throw new RuntimeException(sprintf('Oh no, no result found for %s user and %d anime id', $user->getId(), $animeId));
    }

    public function assertCalls(int $expected): void
    {
        Assert::assertCount($expected, $this->calls);
    }

    private function key(User $user, int $animeId): string
    {
        return $user->getId().$animeId;
    }
}
