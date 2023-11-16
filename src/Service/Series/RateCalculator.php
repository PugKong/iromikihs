<?php

declare(strict_types=1);

namespace App\Service\Series;

use App\Entity\AnimeRateStatus;
use App\Entity\Series;
use App\Entity\SeriesState;
use App\Entity\User;
use App\Repository\AnimeRateRepository;
use App\Repository\AnimeRepository;
use App\Shikimori\Api\Enum\Status;

use function count;
use function in_array;

final readonly class RateCalculator
{
    private AnimeRepository $animes;
    private AnimeRateRepository $animeRates;

    public function __construct(AnimeRepository $animes, AnimeRateRepository $animeRates)
    {
        $this->animes = $animes;
        $this->animeRates = $animeRates;
    }

    public function __invoke(User $user, Series $series): RateCalculation
    {
        $releasedCount = $this->animes->count(['series' => $series, 'status' => Status::RELEASED]);
        $animes = $this->animes->findBy(['series' => $series]);
        $rates = $this->animeRates->findBy(['user' => $user, 'anime' => $animes]);

        $completedOrWatchingCount = 0;
        $droppedCount = 0;
        $scoreSum = 0;
        $scoreCount = 0;
        foreach ($rates as $rate) {
            if (in_array($rate->getStatus(), [
                AnimeRateStatus::COMPLETED,
                AnimeRateStatus::WATCHING,
                AnimeRateStatus::REWATCHING,
                AnimeRateStatus::SKIPPED,
            ])) {
                ++$completedOrWatchingCount;
            }
            if (AnimeRateStatus::DROPPED === $rate->getStatus()) {
                ++$droppedCount;
            }
            if (0 !== $rate->getScore()) {
                ++$scoreCount;
                $scoreSum += $rate->getScore();
            }
        }

        $score = $scoreCount > 0 ? $scoreSum / $scoreCount : 0;

        $isCompleted = $releasedCount === ($completedOrWatchingCount + $droppedCount);
        $state = $isCompleted ? SeriesState::COMPLETE : SeriesState::INCOMPLETE;

        return new RateCalculation(
            releasedCount: $releasedCount,
            ratesCount: count($rates),
            droppedCount: $droppedCount,
            state: $state,
            score: $score,
        );
    }
}
