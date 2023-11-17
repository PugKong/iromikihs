<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\User;
use App\Service\Anime\GetUserSeriesList\AnimeResult;
use App\Service\Anime\GetUserSeriesList\SeriesResult;
use App\Shikimori\Client\Config;
use Symfony\Component\String\UnicodeString;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SeriesList
{
    private User $user;
    /** @var SeriesResult[] */
    private array $series;

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param SeriesResult[] $series
     */
    public function mount(User $user, array $series): void
    {
        $this->user = $user;
        $this->series = $series;
    }

    /**
     * @return SeriesResult[]
     */
    public function getSeries(): array
    {
        return $this->series;
    }

    public function getUrl(AnimeResult $anime): string
    {
        return $this->config->baseUrl.$anime->url;
    }

    public function tryShorteningAnimeNameLength(SeriesResult $series, AnimeResult $anime): string
    {
        $animeName = new UnicodeString($anime->name);
        $animeName = $animeName->lower();
        if ($animeName->length() <= 32) {
            return $anime->name;
        }

        $seriesName = new UnicodeString($series->name);
        $seriesName = $seriesName->lower();
        $commonPrefixLength = 0;
        for ($i = 0; $i < $seriesName->length() && $i < $animeName->length(); ++$i) {
            if ($seriesName->codePointsAt($i) !== $animeName->codePointsAt($i)) {
                break;
            }

            ++$commonPrefixLength;
        }

        if ($animeName->length() - $commonPrefixLength < 4) {
            return $anime->name;
        }

        return $animeName->slice($commonPrefixLength)->trim(' :-')->toString();
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
