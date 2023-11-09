<?php

declare(strict_types=1);

namespace App\Service\Anime;

use App\Entity\Anime;
use App\Entity\AnimeRate;
use App\Entity\Series;
use App\Entity\SeriesRate;

final readonly class GetUserSeriesListResult
{
    /** @var Series[] */
    private array $series;

    /** @var array<string, SeriesRate> */
    private array $seriesRates;

    /** @var array<string, Anime[]> */
    private array $animes;

    /** @var array<int, AnimeRate> */
    private array $animesRates;

    /**
     * @param Series[]     $series
     * @param SeriesRate[] $seriesRates
     * @param Anime[]      $animes
     * @param AnimeRate[]  $animeRates
     */
    public function __construct(
        array $series,
        array $seriesRates,
        array $animes,
        array $animeRates,
    ) {
        $this->series = $series;
        $this->seriesRates = $this->mapSeriesRates($seriesRates);
        $this->animes = $this->mapAnimes($animes);
        $this->animesRates = $this->mapAnimeRates($animeRates);
    }

    /**
     * @return Series[]
     */
    public function getSeries(): array
    {
        return $this->series;
    }

    public function getSeriesRate(Series $series): SeriesRate
    {
        $key = (string) $series->getId();

        return $this->seriesRates[$key];
    }

    /**
     * @return Anime[]
     */
    public function getAnimesBySeries(Series $series): array
    {
        $key = (string) $series->getId();

        return $this->animes[$key];
    }

    public function getAnimeRate(Anime $anime): ?AnimeRate
    {
        $key = $anime->getId();

        return $this->animesRates[$key] ?? null;
    }

    /**
     * @param SeriesRate[] $seriesRates
     *
     * @return array<string, SeriesRate>
     */
    private function mapSeriesRates(array $seriesRates): array
    {
        $map = [];
        foreach ($seriesRates as $seriesRate) {
            $key = (string) $seriesRate->getSeries()->getId();
            $map[$key] = $seriesRate;
        }

        return $map;
    }

    /**
     * @param Anime[] $animes
     *
     * @return array<string, Anime[]>
     */
    private function mapAnimes(array $animes): array
    {
        $map = [];
        foreach ($animes as $anime) {
            $key = $anime->getSeries()?->getId();
            if (null === $key) {
                continue;
            }
            $key = (string) $key;
            $map[$key][] = $anime;
        }

        return $map;
    }

    /**
     * @param AnimeRate[] $animeRates
     *
     * @return array<int, AnimeRate>
     */
    private function mapAnimeRates(array $animeRates): array
    {
        $map = [];
        foreach ($animeRates as $animeRate) {
            $key = $animeRate->getAnime()->getId();
            $map[$key] = $animeRate;
        }

        return $map;
    }
}
