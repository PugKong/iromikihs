<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Entity\UserSyncState;
use App\Message\SyncUserSeriesHandler;
use App\Message\SyncUserSeriesMessage;
use App\Message\SyncUserSeriesRatesMessage;
use App\Service\Shikimori\AnimeSeriesFetcherResult;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\TestDouble\Shikimori\AnimeSeriesFetcherSpy;
use App\Tests\TestDouble\Shikimori\BaseAnimeDataStub;
use App\Tests\Trait\BaseAnimeDataUtil;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class SyncUserSeriesHandlerTest extends MessageHandlerTestCase
{
    use BaseAnimeDataUtil;
    use InteractsWithMessenger;

    public function testSync(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES)
            ->create()
        ;
        $anime = AnimeFactory::createOne();
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime]);

        $seriesFetcher = self::getService(AnimeSeriesFetcherSpy::class);
        $seriesFetcher->addResult(
            $user->object(),
            $anime->getId(),
            new AnimeSeriesFetcherResult(
                $seriesName = 'the series name',
                [$item = self::createAnimeItem(BaseAnimeDataStub::class, $anime->getId())],
            ),
        );

        $handler = self::getService(SyncUserSeriesHandler::class);
        ($handler)(new SyncUserSeriesMessage($user->getId()));

        $series = SeriesFactory::all();
        self::assertCount(1, $series);
        self::assertSame($seriesName, $series[0]->getName());
        self::assertBaseItemDataEqualsAnimeData($item, $anime->object());

        $messages = $this->transport('async')->queue()->messages(SyncUserSeriesMessage::class);
        self::assertCount(1, $messages);
        self::assertEquals($user->getId(), $messages[0]->userId);
    }

    public function testStartUserSeriesSyncWhenFullySynced(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES)
            ->create()
        ;

        $handler = self::getService(SyncUserSeriesHandler::class);
        ($handler)(new SyncUserSeriesMessage($user->getId()));

        $messages = $this->transport('async')->queue()->messages(SyncUserSeriesMessage::class);
        self::assertCount(0, $messages);

        $messages = $this->transport('async')->queue()->messages(SyncUserSeriesRatesMessage::class);
        self::assertCount(1, $messages);
    }
}
