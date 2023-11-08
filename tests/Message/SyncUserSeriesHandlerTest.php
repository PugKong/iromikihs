<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Entity\UserSeriesState;
use App\Message\SyncUserSeries;
use App\Message\SyncUserSeriesHandler;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\SeriesStateFactory;
use App\Tests\Factory\UserFactory;

final class SyncUserSeriesHandlerTest extends MessageHandlerTestCase
{
    public function testCreateSeriesState(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => UserAnimeStatus::COMPLETED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2, 'status' => UserAnimeStatus::PLANNED]);

        $handler = self::getService(SyncUserSeriesHandler::class);
        ($handler)(new SyncUserSeries($user->getId()));

        $userSeries = SeriesStateFactory::find(['series' => $series]);
        self::assertEquals($user->object(), $userSeries->getUser());
        self::assertSame(UserSeriesState::INCOMPLETE, $userSeries->getState());
    }
}
