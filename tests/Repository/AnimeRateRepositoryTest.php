<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\AnimeRateRepository;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\UserFactory;
use DateTimeImmutable;

final class AnimeRateRepositoryTest extends RepositoryTestCase
{
    public function testFindNextAnimeToSyncSeriesByUserNullSeries(): void
    {
        $user = UserFactory::createOne();
        $anime = AnimeFactory::createOne();
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime]);

        $repository = self::getService(AnimeRateRepository::class);
        $next = $repository->findNextAnimeToSyncSeriesByUser($user->object());
        self::assertSame($anime->object(), $next);

        $next = $repository->findNextAnimeToSyncSeriesByUser(UserFactory::createOne()->object());
        self::assertNull($next);
    }

    public function testFindNextAnimeToSyncSeriesByUserOutdatedSeries(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne(['updatedAt' => new DateTimeImmutable('-1 month -1 day')]);
        $anime = AnimeFactory::createOne(['series' => $series]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime]);

        $repository = self::getService(AnimeRateRepository::class);
        $next = $repository->findNextAnimeToSyncSeriesByUser($user->object());
        self::assertSame($anime->object(), $next);

        $next = $repository->findNextAnimeToSyncSeriesByUser(UserFactory::createOne()->object());
        self::assertNull($next);
    }

    public function testFindNextAnimeToSyncSeriesByUserSyncedSeries(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne(['updatedAt' => new DateTimeImmutable('-1 month +1 day')]);
        $anime = AnimeFactory::createOne(['series' => $series]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime]);

        $repository = self::getService(AnimeRateRepository::class);
        $next = $repository->findNextAnimeToSyncSeriesByUser($user->object());
        self::assertNull($next);
    }
}
