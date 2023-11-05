<?php

declare(strict_types=1);

namespace App\Service\Anime;

use App\Entity\Anime;
use App\Entity\AnimeRate;
use App\Entity\User;
use App\Repository\AnimeRateRepository;
use App\Repository\AnimeRepository;
use App\Shikimori\Api\User\AnimeItem;
use App\Shikimori\Api\User\AnimeRatesResponse;
use Doctrine\ORM\EntityManagerInterface;

use function array_key_exists;

final readonly class SyncUserList
{
    private AnimeRepository $animes;
    private AnimeRateRepository $rates;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AnimeRepository $animes,
        AnimeRateRepository $rates,
        EntityManagerInterface $entityManager,
    ) {
        $this->animes = $animes;
        $this->rates = $rates;
        $this->entityManager = $entityManager;
    }

    public function __invoke(SyncUserListData $data): void
    {
        $rateIds = array_map(fn (AnimeRatesResponse $r) => $r->id, $data->rates);
        $syncRates = $this->rates->findByUserOrIdsIndexedById($data->user, $rateIds);

        foreach ($data->rates as $rateData) {
            $anime = $this->animes->findOrNew($rateData->anime->id);
            $this->setAnimeData($anime, $rateData->anime);
            $this->entityManager->persist($anime);

            if (array_key_exists($rateData->id, $syncRates)) {
                $rate = $syncRates[$rateData->id];
                unset($syncRates[$rateData->id]);
            } else {
                $rate = new AnimeRate();
                $rate->setId($rateData->id);
            }
            $this->setRateData($rate, $rateData, $data->user, $anime);
            $this->entityManager->persist($rate);
        }

        foreach ($syncRates as $rate) {
            $this->entityManager->remove($rate);
        }

        $this->entityManager->flush();
    }

    private function setAnimeData(Anime $anime, AnimeItem $data): void
    {
        $anime->setName($data->name);
        $anime->setUrl($data->url);
        $anime->setKind($data->kind);
        $anime->setStatus($data->status);
    }

    private function setRateData(AnimeRate $rate, AnimeRatesResponse $data, User $user, Anime $anime): void
    {
        $rate->setUser($user);
        $rate->setAnime($anime);
        $rate->setScore($data->score);
        $rate->setStatus($data->status);
    }
}
