<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\User;
use App\Service\Anime\GetUserSeriesList\SeriesResult;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SeriesList
{
    private User $user;
    /** @var SeriesResult[] */
    private array $series;

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

    public function getUser(): User
    {
        return $this->user;
    }
}
