<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\SeriesState;
use App\Entity\User;
use App\Repository\AnimeRateRepository;
use App\Repository\SeriesRateRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @phpstan-type NavBarItem array{route: string, label: string, active: bool, count?: int}
 */
#[AsTwigComponent]
final class NavBar
{
    private User $user;
    private string $route;

    private AnimeRateRepository $animeRates;
    private SeriesRateRepository $seriesRates;

    public function __construct(AnimeRateRepository $animeRates, SeriesRateRepository $seriesRates)
    {
        $this->animeRates = $animeRates;
        $this->seriesRates = $seriesRates;
    }

    public function mount(User $user, string $route): void
    {
        $this->user = $user;
        $this->route = $route;
    }

    /**
     * @phpstan-return NavBarItem[]
     */
    public function getItems(): array
    {
        $items = [];

        $items[] = $this->makeItem(
            'app_anime_index',
            'Anime list',
            $this->animeRates->count(['user' => $this->user]),
        );
        $items[] = $this->makeItem(
            'app_series_incomplete',
            'Incomplete series',
            $this->seriesRates->count(['user' => $this->user, 'state' => SeriesState::INCOMPLETE]),
        );
        $items[] = $this->makeItem(
            'app_series_complete',
            'Completed series',
            $this->seriesRates->count(['user' => $this->user, 'state' => SeriesState::COMPLETE]),
        );
        $items[] = $this->makeItem(
            'app_series_dropped',
            'Dropped series',
            $this->seriesRates->count(['user' => $this->user, 'state' => SeriesState::DROPPED]),
        );
        $items[] = $this->makeItem('app_profile', 'Profile');
        $items[] = $this->makeItem('app_logout', 'Log out');

        return $items;
    }

    /**
     * @phpstan-return NavBarItem
     */
    private function makeItem(string $route, string $label, int $count = null): array
    {
        $item = [
            'route' => $route,
            'label' => $label,
            'active' => $this->route === $route,
        ];
        if (null !== $count && 0 !== $count) {
            $item['count'] = $count;
        }

        return $item;
    }
}
