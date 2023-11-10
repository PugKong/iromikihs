<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\Anime;
use App\Entity\AnimeRate;
use App\Entity\Series;
use App\Entity\SeriesRate;
use App\Entity\SeriesState;
use App\Entity\User;
use App\Service\Anime\GetUserSeriesList;
use App\Service\Anime\GetUserSeriesListResult;
use App\Shikimori\Client\Config;
use Symfony\Component\String\UnicodeString;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SeriesList
{
    private GetUserSeriesListResult $list;

    private Config $config;
    private GetUserSeriesList $getUserSeriesList;

    public function __construct(
        Config $config,
        GetUserSeriesList $getUserSeriesList,
    ) {
        $this->config = $config;
        $this->getUserSeriesList = $getUserSeriesList;
    }

    public function mount(User $user, string $state): void
    {
        $state = SeriesState::from($state);
        $this->list = ($this->getUserSeriesList)($user, $state);
    }

    /**
     * @return Series[]
     */
    public function getSeries(): array
    {
        return $this->list->getSeries();
    }

    public function getSeriesRate(Series $series): SeriesRate
    {
        return $this->list->getSeriesRate($series);
    }

    /**
     * @return Anime[]
     */
    public function getAnimesBySeries(Series $series): array
    {
        return $this->list->getAnimesBySeries($series);
    }

    public function getAnimeRate(Anime $anime): ?AnimeRate
    {
        return $this->list->getAnimeRate($anime);
    }

    public function getUrl(Anime $anime): string
    {
        return $this->config->baseUrl.$anime->getUrl();
    }

    public function tryShorteningAnimeNameLength(Series $series, Anime $anime): string
    {
        $animeName = new UnicodeString($anime->getName());
        $animeName = $animeName->lower();
        if ($animeName->length() <= 32) {
            return $anime->getName();
        }

        $seriesName = new UnicodeString($series->getName());
        $seriesName = $seriesName->lower();
        $commonPrefixLength = 0;
        for ($i = 0; $i < $seriesName->length() && $i < $animeName->length(); ++$i) {
            if ($seriesName->codePointsAt($i) !== $animeName->codePointsAt($i)) {
                break;
            }

            ++$commonPrefixLength;
        }

        if ($animeName->length() - $commonPrefixLength < 4) {
            return $anime->getName();
        }

        return $animeName->slice($commonPrefixLength)->trim(' :-')->toString();
    }
}
