<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\AnimeRateRepository;
use App\Shikimori\Api\Enum\UserAnimeStatus;
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

    public function testFindSeriesIdsByUser(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series]);
        $anime2 = AnimeFactory::createOne(['series' => $series]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2]);

        $repository = self::getService(AnimeRateRepository::class);
        $userSeries = $repository->findSeriesIdsByUser($user->object());
        self::assertEquals([$series->getId()], $userSeries);

        $userSeries = $repository->findSeriesIdsByUser(UserFactory::createOne()->object());
        self::assertEquals([], $userSeries);
    }

    public function testCountByUserAndSeries(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series]);
        $anime2 = AnimeFactory::createOne(['series' => $series]);
        $anime3 = AnimeFactory::createOne(['series' => $series]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => UserAnimeStatus::WATCHING]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2, 'status' => UserAnimeStatus::COMPLETED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime3, 'status' => UserAnimeStatus::PLANNED]);

        $repository = self::getService(AnimeRateRepository::class);
        $actual = $repository->countByUserAndSeries($user->object(), $series->object());
        self::assertSame(3, $actual);

        $actual = $repository->countByUserAndSeries(
            $user->object(),
            $series->object(),
            statuses: [UserAnimeStatus::WATCHING, UserAnimeStatus::COMPLETED],
        );
        self::assertSame(2, $actual);

        $actual = $repository->countByUserAndSeries(UserFactory::createOne()->object(), $series->object());
        self::assertSame(0, $actual);
    }
}
