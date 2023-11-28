<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\SeriesState;
use App\Entity\User;
use App\Repository\AnimeRateRepository;
use App\Repository\SeriesRateRepository;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @phpstan-type NavBarItem array{path: string, label: string, active: bool, count?: int}
 */
#[AsTwigComponent]
final class NavBar
{
    private User $user;
    private ?string $path;

    private AnimeRateRepository $animeRates;
    private SeriesRateRepository $seriesRates;
    private RouterInterface $router;

    public function __construct(
        AnimeRateRepository $animeRates,
        SeriesRateRepository $seriesRates,
        RouterInterface $router,
    ) {
        $this->animeRates = $animeRates;
        $this->seriesRates = $seriesRates;
        $this->router = $router;
    }

    public function mount(User $user, ?string $path): void
    {
        $this->user = $user;
        $this->path = $path;
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

        return $items;
    }

    /**
     * @phpstan-return NavBarItem
     */
    private function makeItem(string $route, string $label, int $count = null): array
    {
        $path = $this->router->generate($route);
        $item = [
            'path' => $path,
            'label' => $label,
            'active' => $path === $this->path,
        ];
        if (null !== $count && 0 !== $count) {
            $item['count'] = $count;
        }

        return $item;
    }
}
