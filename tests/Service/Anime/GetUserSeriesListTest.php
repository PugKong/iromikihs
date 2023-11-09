<?php

declare(strict_types=1);

namespace App\Tests\Service\Anime;

use App\Entity\SeriesState;
use App\Service\Anime\GetUserSeriesList;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\SeriesRateFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Service\ServiceTestCase;

final class GetUserSeriesListTest extends ServiceTestCase
{
    public function testRetrieveList(): void
    {
        $user = UserFactory::createOne();

        $completedSeries = SeriesFactory::createOne();
        $completedSeriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $completedSeries,
            'state' => SeriesState::COMPLETE,
        ]);
        $completedAnime1 = AnimeFactory::createOne(['id' => 1, 'series' => $completedSeries]);
        $completedAnime2 = AnimeFactory::createOne(['id' => 2, 'series' => $completedSeries]);
        $completedAnime1Rate = AnimeRateFactory::createOne(['user' => $user, 'anime' => $completedAnime1]);
        $completedAnime2Rate = AnimeRateFactory::createOne(['user' => $user, 'anime' => $completedAnime2]);

        $incompleteSeries = SeriesFactory::createOne();
        SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $incompleteSeries,
            'state' => SeriesState::INCOMPLETE,
        ]);
        $incompleteAnime1 = AnimeFactory::createOne(['series' => $incompleteSeries]);
        $incompleteAnime2 = AnimeFactory::createOne(['series' => $incompleteSeries]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $incompleteAnime1]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $incompleteAnime2]);

        $service = self::getService(GetUserSeriesList::class);

        $result = ($service)($user->object(), SeriesState::COMPLETE);
        self::assertEquals([$completedSeries->object()], $result->getSeries());
        self::assertEquals($completedSeriesRate->object(), $result->getSeriesRate($completedSeries->object()));
        self::assertEquals(
            [$completedAnime1->object(), $completedAnime2->object()],
            $result->getAnimesBySeries($completedSeries->object()),
        );
        self::assertEquals($completedAnime1Rate->object(), $result->getAnimeRate($completedAnime1->object()));
        self::assertEquals($completedAnime2Rate->object(), $result->getAnimeRate($completedAnime2->object()));

        $result = ($service)($user->object(), SeriesState::INCOMPLETE);
        self::assertEquals([$incompleteSeries->object()], $result->getSeries());

        $result = ($service)(UserFactory::createOne()->object(), SeriesState::COMPLETE);
        self::assertEquals([], $result->getSeries());
    }
}
