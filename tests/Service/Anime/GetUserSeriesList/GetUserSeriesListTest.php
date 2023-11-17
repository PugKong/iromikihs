<?php

declare(strict_types=1);

namespace App\Tests\Service\Anime\GetUserSeriesList;

use App\Entity\Anime;
use App\Entity\AnimeRate;
use App\Entity\Series;
use App\Entity\SeriesRate;
use App\Entity\SeriesState;
use App\Service\Anime\GetUserSeriesList\AnimeResult;
use App\Service\Anime\GetUserSeriesList\GetUserSeriesList;
use App\Service\Anime\GetUserSeriesList\SeriesResult;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\SeriesRateFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Service\ServiceTestCase;
use Zenstruck\Foundry\Proxy;

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
        $incompleteSeriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $incompleteSeries,
            'state' => SeriesState::INCOMPLETE,
        ]);
        $incompleteAnime1 = AnimeFactory::createOne(['id' => 3, 'series' => $incompleteSeries]);
        $incompleteAnime2 = AnimeFactory::createOne(['id' => 4, 'series' => $incompleteSeries]);
        $incompleteAnime1Rate = AnimeRateFactory::createOne(['user' => $user, 'anime' => $incompleteAnime1]);
        $incompleteAnime2Rate = AnimeRateFactory::createOne(['user' => $user, 'anime' => $incompleteAnime2]);

        $service = self::getService(GetUserSeriesList::class);

        $actual = ($service)($user->object(), SeriesState::COMPLETE);
        self::assertEquals(
            [
                self::createSeriesResult(
                    $completedSeries,
                    $completedSeriesRate,
                    [
                        self::createAnimeResult($completedAnime1, $completedAnime1Rate),
                        self::createAnimeResult($completedAnime2, $completedAnime2Rate),
                    ],
                ),
            ],
            $actual,
        );

        $actual = ($service)($user->object(), SeriesState::INCOMPLETE);
        self::assertEquals(
            [
                self::createSeriesResult(
                    $incompleteSeries,
                    $incompleteSeriesRate,
                    [
                        self::createAnimeResult($incompleteAnime1, $incompleteAnime1Rate),
                        self::createAnimeResult($incompleteAnime2, $incompleteAnime2Rate),
                    ],
                ),
            ],
            $actual,
        );

        $actual = ($service)(UserFactory::createOne()->object(), SeriesState::COMPLETE);
        self::assertEquals([], $actual);
    }

    /**
     * @param Series|Proxy<Series>         $series
     * @param SeriesRate|Proxy<SeriesRate> $seriesRate
     * @param AnimeResult[]                $animes
     */
    private static function createSeriesResult(
        Series|Proxy $series,
        SeriesRate|Proxy $seriesRate,
        array $animes,
    ): SeriesResult {
        return new SeriesResult(
            id: (string) $series->getId(),
            name: $series->getName(),
            seriesRateId: (string) $seriesRate->getId(),
            state: $seriesRate->getState(),
            score: $seriesRate->getScore(),
            animes: $animes,
        );
    }

    /**
     * @param Anime|Proxy<Anime>         $anime
     * @param AnimeRate|Proxy<AnimeRate> $animeRate
     */
    private static function createAnimeResult(Anime|Proxy $anime, AnimeRate|Proxy $animeRate): AnimeResult
    {
        return new AnimeResult(
            id: $anime->getId(),
            kind: $anime->getKind(),
            status: $anime->getStatus(),
            name: $anime->getName(),
            url: $anime->getUrl(),
            state: $animeRate->getStatus(),
            score: $animeRate->getScore(),
        );
    }
}
