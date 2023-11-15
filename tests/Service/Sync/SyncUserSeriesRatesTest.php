<?php

declare(strict_types=1);

namespace App\Tests\Service\Sync;

use App\Entity\SeriesState;
use App\Entity\UserSyncState;
use App\Service\Sync\SyncUserSeriesRates;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\SeriesRateFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Service\ServiceTestCase;
use DateTimeImmutable;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class SyncUserSeriesRatesTest extends ServiceTestCase
{
    use ClockSensitiveTrait;

    public static function incompleteProvider(): array
    {
        return [
            'no rate' => [null],
            'planned' => [UserAnimeStatus::PLANNED],
            'on hold' => [UserAnimeStatus::ON_HOLD],
        ];
    }

    public static function completeProvider(): array
    {
        return [
            'watching' => [UserAnimeStatus::WATCHING],
            'rewatching' => [UserAnimeStatus::REWATCHING],
            'completed' => [UserAnimeStatus::COMPLETED],
            'dropped' => [UserAnimeStatus::DROPPED],
        ];
    }

    /**
     * @dataProvider incompleteProvider
     */
    public function testCreateIncompleteSeries(?UserAnimeStatus $status): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => UserAnimeStatus::COMPLETED]);
        if (null !== $status) {
            AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2, 'status' => $status]);
        }

        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        $seriesRate = SeriesRateFactory::find(['series' => $series]);
        self::assertEquals($user->object(), $seriesRate->getUser());
        self::assertSame(SeriesState::INCOMPLETE, $seriesRate->getState());
    }

    /**
     * @dataProvider completeProvider
     */
    public function testCreateCompleteSeries(UserAnimeStatus $status): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => UserAnimeStatus::COMPLETED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2, 'status' => $status]);

        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        $seriesRate = SeriesRateFactory::find(['series' => $series]);
        self::assertEquals($user->object(), $seriesRate->getUser());
        self::assertSame(SeriesState::COMPLETE, $seriesRate->getState());
    }

    /**
     * @dataProvider dontCreateUserSeriesWhenOnlyOneOrLessAnimesAreReleasedProvider
     */
    public function testDontCreateUserSeriesWhenOnlyOneOrLessAnimesAreReleased(Status $status1, Status $status2): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => $status1]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => $status2]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => UserAnimeStatus::WATCHING]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2, 'status' => UserAnimeStatus::WATCHING]);

        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        $seriesRates = SeriesRateFactory::all();
        self::assertCount(0, $seriesRates);
    }

    public static function dontCreateUserSeriesWhenOnlyOneOrLessAnimesAreReleasedProvider(): array
    {
        return [
            'no released' => [Status::ONGOING, Status::ONGOING],
            'one released' => [Status::ONGOING, Status::RELEASED],
        ];
    }

    public function testDontCreateUserSeriesWhenUserHasOnlyDroppedItems(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => UserAnimeStatus::DROPPED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2, 'status' => UserAnimeStatus::DROPPED]);

        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        $seriesRates = SeriesRateFactory::all();
        self::assertCount(0, $seriesRates);
    }

    /**
     * @dataProvider incompleteProvider
     */
    public function testUpdateIncompleteSeries(?UserAnimeStatus $status): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => UserAnimeStatus::COMPLETED]);
        if (null !== $status) {
            AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2, 'status' => $status]);
        }
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::COMPLETE,
        ]);

        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        self::assertSame(SeriesState::INCOMPLETE, $seriesRate->getState());
    }

    /**
     * @dataProvider completeProvider
     */
    public function testUpdateCompleteSeries(UserAnimeStatus $status): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => UserAnimeStatus::COMPLETED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2, 'status' => $status]);
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::INCOMPLETE,
        ]);

        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        self::assertSame(SeriesState::COMPLETE, $seriesRate->getState());
    }

    public function testCalculateScore(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne([
            'user' => $user,
            'anime' => $anime1,
            'status' => UserAnimeStatus::COMPLETED,
            'score' => 5,
        ]);
        AnimeRateFactory::createOne([
            'user' => $user,
            'anime' => $anime2,
            'status' => UserAnimeStatus::COMPLETED,
            'score' => 6,
        ]);

        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        $seriesRate = SeriesRateFactory::find(['series' => $series]);
        self::assertSame(5.5, $seriesRate->getScore());
    }

    public function testHandleZeroScores(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne([
            'user' => $user,
            'anime' => $anime1,
            'status' => UserAnimeStatus::COMPLETED,
            'score' => 0,
        ]);
        AnimeRateFactory::createOne([
            'user' => $user,
            'anime' => $anime2,
            'status' => UserAnimeStatus::COMPLETED,
            'score' => 0,
        ]);

        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        $seriesRate = SeriesRateFactory::find(['series' => $series]);
        self::assertSame(0.0, $seriesRate->getScore());
    }

    public function testUpdatesSyncStatus(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;

        self::mockTime($now = new DateTimeImmutable('2007-01-02 03:04:05'));
        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        self::assertNull($user->getSync()->getState());
        self::assertEquals($now, $user->getSync()->getSyncedAt());
    }

    /**
     * @dataProvider invalidSyncStateProvider
     */
    public function testInvalidSyncState(?UserSyncState $state): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: $state)
            ->create()
        ;

        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        self::assertSame($state, $user->getSync()->getState());
    }

    public static function invalidSyncStateProvider(): array
    {
        return [
            [null],
            [UserSyncState::LINK_ACCOUNT],
            [UserSyncState::ANIME_RATES],
            [UserSyncState::SERIES],
            [UserSyncState::FAILED],
        ];
    }

    public function testNoLinkedAccount(): void
    {
        $user = UserFactory::new()->withSyncStatus(state: $state = UserSyncState::SERIES_RATES)->create();

        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        self::assertSame($state, $user->getSync()->getState());
    }

    public function testDontOverrideDroppedState(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;
        $series = SeriesFactory::createOne();
        $completedAnime = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne([
            'user' => $user,
            'anime' => $completedAnime,
            'status' => UserAnimeStatus::COMPLETED,
        ]);
        SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::DROPPED,
        ]);

        $service = self::getService(SyncUserSeriesRates::class);
        ($service)($user->object());

        $seriesRate = SeriesRateFactory::find(['series' => $series]);
        self::assertEquals($user->object(), $seriesRate->getUser());
        self::assertSame(SeriesState::DROPPED, $seriesRate->getState());
    }
}
