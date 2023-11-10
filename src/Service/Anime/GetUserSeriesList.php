<?php

declare(strict_types=1);

namespace App\Service\Anime;

use App\Entity\SeriesRate;
use App\Entity\SeriesState;
use App\Entity\User;
use App\Repository\AnimeRateRepository;
use App\Repository\AnimeRepository;
use App\Repository\SeriesRateRepository;
use App\Repository\SeriesRepository;
use Symfony\Component\Uid\UuidV7;

final readonly class GetUserSeriesList
{
    private SeriesRepository $series;
    private SeriesRateRepository $seriesRates;
    private AnimeRepository $animes;
    private AnimeRateRepository $animeRates;

    public function __construct(
        SeriesRepository $series,
        SeriesRateRepository $seriesRates,
        AnimeRepository $animes,
        AnimeRateRepository $animeRates,
    ) {
        $this->series = $series;
        $this->seriesRates = $seriesRates;
        $this->animes = $animes;
        $this->animeRates = $animeRates;
    }

    public function __invoke(User $user, SeriesState $state): GetUserSeriesListResult
    {
        $seriesRates = $this->seriesRates->findBy(['user' => $user, 'state' => $state]);
        $seriesIds = $this->seriesRatesToSeriesIds($seriesRates);
        $series = $this->series->findBy(['id' => $seriesIds], ['name' => 'ASC']);

        $animes = $this->animes->findBy(['series' => $seriesIds], ['id' => 'ASC']);
        $animeRates = $this->animeRates->findBy(['user' => $user, 'anime' => $animes]);

        return new GetUserSeriesListResult(
            series: $series,
            seriesRates: $seriesRates,
            animes: $animes,
            animeRates: $animeRates,
        );
    }

    /**
     * @param SeriesRate[] $seriesRates
     *
     * @return UuidV7[]
     */
    private function seriesRatesToSeriesIds(array $seriesRates): array
    {
        $seriesIds = [];
        foreach ($seriesRates as $seriesRate) {
            $seriesIds[] = $seriesRate->getSeries()->getId();
        }

        return $seriesIds;
    }
}
