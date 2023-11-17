<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\AnimeRateStatus;
use App\Repository\UserRatedAnime;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class AnimeList
{
    /** @var UserRatedAnime[] */
    private array $watchingOrRewatching = [];
    /** @var UserRatedAnime[] */
    private array $onHold = [];
    /** @var UserRatedAnime[] */
    private array $planned = [];
    /** @var UserRatedAnime[] */
    private array $completed = [];
    /** @var UserRatedAnime[] */
    private array $droppedOrSKipped = [];

    /**
     * @param UserRatedAnime[] $items
     */
    public function mount(array $items): void
    {
        foreach ($items as $item) {
            if (AnimeRateStatus::WATCHING === $item->rateStatus || AnimeRateStatus::REWATCHING === $item->rateStatus) {
                $this->watchingOrRewatching[] = $item;
            }
            if (AnimeRateStatus::ON_HOLD === $item->rateStatus) {
                $this->onHold[] = $item;
            }
            if (AnimeRateStatus::PLANNED === $item->rateStatus) {
                $this->planned[] = $item;
            }
            if (AnimeRateStatus::COMPLETED === $item->rateStatus) {
                $this->completed[] = $item;
            }
            if (AnimeRateStatus::DROPPED === $item->rateStatus || AnimeRateStatus::SKIPPED === $item->rateStatus) {
                $this->droppedOrSKipped[] = $item;
            }
        }
    }

    /**
     * @return UserRatedAnime[]
     */
    public function getWatchingOrRewatching(): array
    {
        return $this->watchingOrRewatching;
    }

    /**
     * @return UserRatedAnime[]
     */
    public function getOnHold(): array
    {
        return $this->onHold;
    }

    /**
     * @return UserRatedAnime[]
     */
    public function getPlanned(): array
    {
        return $this->planned;
    }

    /**
     * @return UserRatedAnime[]
     */
    public function getCompleted(): array
    {
        return $this->completed;
    }

    /**
     * @return UserRatedAnime[]
     */
    public function getDroppedOrSKipped(): array
    {
        return $this->droppedOrSKipped;
    }
}
