<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Entity\AnimeRateStatus;
use App\Entity\SeriesState;
use App\Entity\UserSyncState;
use App\Message\SyncUserSeriesRatesHandler;
use App\Message\SyncUserSeriesRatesMessage;
use App\Shikimori\Api\Enum\Status;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\SeriesRateFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Component\Uid\Uuid;

final class SyncUserSeriesRatesHandlerTest extends MessageHandlerTestCase
{
    public function testCreateSeriesState(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => AnimeRateStatus::COMPLETED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2, 'status' => AnimeRateStatus::PLANNED]);

        $handler = self::getService(SyncUserSeriesRatesHandler::class);
        ($handler)(new SyncUserSeriesRatesMessage($user->getId()));

        $seriesRate = SeriesRateFactory::find(['series' => $series]);
        self::assertEquals($user->object(), $seriesRate->getUser());
        self::assertSame(SeriesState::INCOMPLETE, $seriesRate->getState());
    }

    public function testUserNotFound(): void
    {
        $this->expectNotToPerformAssertions();

        $handler = self::getService(SyncUserSeriesRatesHandler::class);
        ($handler)(new SyncUserSeriesRatesMessage(Uuid::v7()));
    }
}
