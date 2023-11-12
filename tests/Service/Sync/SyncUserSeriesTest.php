<?php

declare(strict_types=1);

namespace App\Tests\Service\Sync;

use App\Entity\UserSyncState;
use App\Message\SyncUserSeriesRatesMessage;
use App\Service\Shikimori\AnimeSeriesFetcherResult;
use App\Service\Sync\SyncUserSeries;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Service\ServiceTestCase;
use App\Tests\TestDouble\Shikimori\AnimeSeriesFetcherSpy;
use App\Tests\TestDouble\Shikimori\BaseAnimeDataStub;
use App\Tests\Trait\BaseAnimeDataUtil;
use DateTimeImmutable;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class SyncUserSeriesTest extends ServiceTestCase
{
    use BaseAnimeDataUtil;
    use ClockSensitiveTrait;
    use InteractsWithMessenger;

    public function testCreateNewSeries(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES)
            ->create()
        ;
        $firstAnime = AnimeFactory::createOne([
            'id' => $firstAnimeId = 1,
            'series' => null,
            'name' => 'change me',
            'url' => '/animes/change-me',
            'kind' => Kind::MOVIE,
            'status' => Status::ANONS,
        ]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $firstAnime]);

        $seriesFetcher = self::getService(AnimeSeriesFetcherSpy::class);
        $seriesFetcher->addResult(
            $user->object(),
            $firstAnimeId,
            new AnimeSeriesFetcherResult($seriesName = 'series name', [
                $firstItem = $this->createAnimeItem(BaseAnimeDataStub::class, $firstAnimeId),
                $secondItem = $this->createAnimeItem(BaseAnimeDataStub::class, $secondAnimeId = 2),
            ]),
        );

        self::mockTime($updatedAt = new DateTimeImmutable('2007-01-02 03:04:05'));
        $service = self::getService(SyncUserSeries::class);
        ($service)($user->object());

        $seriesFetcher->assertCalls(1);

        $series = SeriesFactory::all();
        self::assertCount(1, $series);
        $series = $series[0];
        self::assertSame($seriesName, $series->getName());
        self::assertEquals($updatedAt, $series->getUpdatedAt());

        self::assertSame($series->object(), $firstAnime->getSeries());
        self::assertBaseItemDataEqualsAnimeData($firstItem, $firstAnime->object());

        $secondAnime = AnimeFactory::find($secondAnimeId);
        self::assertSame($series->object(), $secondAnime->getSeries());
        self::assertBaseItemDataEqualsAnimeData($secondItem, $secondAnime->object());
    }

    public function testUseExistingSeries(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES)
            ->create()
        ;
        $series = SeriesFactory::createOne([
            'name' => 'change me',
            'updated_at' => new DateTimeImmutable('2006-01-02 03:04:05'),
        ]);
        $firstAnime = AnimeFactory::createOne([
            'id' => $firstAnimeId = 1,
            'series' => $series,
        ]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $firstAnime]);

        $seriesFetcher = self::getService(AnimeSeriesFetcherSpy::class);
        $seriesFetcher->addResult(
            $user->object(),
            $firstAnimeId,
            new AnimeSeriesFetcherResult($seriesName = 'new series name', [
                $this->createAnimeItem(BaseAnimeDataStub::class, $firstAnimeId),
                $this->createAnimeItem(BaseAnimeDataStub::class, $secondAnimeId = 2),
            ]),
        );

        self::mockTime($updatedAt = new DateTimeImmutable('2007-01-02 03:04:05'));
        $service = self::getService(SyncUserSeries::class);
        ($service)($user->object());

        self::assertCount(1, SeriesFactory::all());
        self::assertSame($seriesName, $series->getName());
        self::assertEquals($updatedAt, $series->getUpdatedAt());

        self::assertSame($series->object(), $firstAnime->getSeries());
        $secondAnime = AnimeFactory::find($secondAnimeId);
        self::assertSame($series->object(), $secondAnime->getSeries());
    }

    public function testFinishDataSynchronization(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES)
            ->create()
        ;

        $service = self::getService(SyncUserSeries::class);
        ($service)($user->object());

        self::assertSame(UserSyncState::SERIES_RATES, $user->getSync()->getState());

        $this->transport('async')->queue()->assertCount(1);
        $messages = $this->transport('async')->queue()->messages(SyncUserSeriesRatesMessage::class);
        self::assertCount(1, $messages);
        self::assertEquals($user->getId(), $messages[0]->userId);
    }

    /**
     * @dataProvider invalidSyncStateProvider
     */
    public function testInvalidSyncState(?UserSyncState $state): void
    {
        $user = UserFactory::new()->withLinkedAccount()->withSyncStatus(state: $state)->create();

        $service = self::getService(SyncUserSeries::class);
        ($service)($user->object());

        self::assertSame($state, $user->getSync()->getState());
    }

    public static function invalidSyncStateProvider(): array
    {
        return [
            [null],
            [UserSyncState::ANIME_RATES],
            [UserSyncState::SERIES_RATES],
            [UserSyncState::LINK_ACCOUNT],
            [UserSyncState::FAILED],
        ];
    }

    public function testNoLinkedAccount(): void
    {
        $user = UserFactory::new()->withSyncStatus(state: $state = UserSyncState::SERIES)->create();

        $service = self::getService(SyncUserSeries::class);
        ($service)($user->object());

        self::assertSame($state, $user->getSync()->getState());
    }
}
