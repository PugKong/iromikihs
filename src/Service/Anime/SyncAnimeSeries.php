<?php

declare(strict_types=1);

namespace App\Service\Anime;

use App\Entity\Series;
use App\Repository\AnimeRepository;
use App\Shikimori\Api\BaseAnimeData;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class SyncAnimeSeries
{
    private AnimeRepository $animes;
    private ClockInterface $clock;
    private EntityManagerInterface $entityManager;

    public function __construct(AnimeRepository $animes, ClockInterface $clock, EntityManagerInterface $entityManager)
    {
        $this->animes = $animes;
        $this->clock = $clock;
        $this->entityManager = $entityManager;
    }

    /**
     * @param BaseAnimeData[] $animesData
     */
    public function __invoke(string $name, array $animesData): void
    {
        $series = null;
        $animes = [];
        foreach ($animesData as $animeData) {
            $anime = $this->animes->findOrNew($animeData->id);
            if (null === $series && null !== $anime->getSeries()) {
                $series = $anime->getSeries();
            }
            $anime->updateFromBaseData($animeData);

            $animes[] = $anime;
        }

        if (null === $series) {
            $series = new Series();
        }

        foreach ($animes as $anime) {
            $anime->setSeries($series);
            $this->entityManager->persist($anime);
        }

        $series->setName($name);
        $series->setUpdatedAt($this->clock->now());
        $this->entityManager->persist($series);

        $this->entityManager->flush();
    }
}
