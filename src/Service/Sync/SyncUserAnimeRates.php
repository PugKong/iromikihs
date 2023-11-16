<?php

declare(strict_types=1);

namespace App\Service\Sync;

use App\Entity\Anime;
use App\Entity\AnimeRate;
use App\Entity\AnimeRateStatus;
use App\Entity\User;
use App\Entity\UserSyncState;
use App\Message\SyncUserSeriesMessage;
use App\Repository\AnimeRateRepository;
use App\Repository\AnimeRepository;
use App\Service\Shikimori\TokenStorage;
use App\Shikimori\Api\User\AnimeRatesRequest;
use App\Shikimori\Api\User\AnimeRatesResponse;
use App\Shikimori\Client\Shikimori;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

use function array_key_exists;

final readonly class SyncUserAnimeRates
{
    private TokenStorage $tokenStorage;
    private Shikimori $shikimori;
    private AnimeRepository $animes;
    private AnimeRateRepository $rates;
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $bus;

    public function __construct(
        TokenStorage $tokenStorage,
        Shikimori $shikimori,
        AnimeRepository $animes,
        AnimeRateRepository $rates,
        EntityManagerInterface $entityManager,
        MessageBusInterface $bus,
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->shikimori = $shikimori;
        $this->animes = $animes;
        $this->rates = $rates;
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }

    public function __invoke(User $user): void
    {
        $sync = $user->getSync();
        if (!$sync->isLinked() || UserSyncState::ANIME_RATES !== $sync->getState()) {
            return;
        }

        $token = $this->tokenStorage->retrieve($user);
        $request = new AnimeRatesRequest($token, $sync->getAccountId());
        $ratesData = $this->shikimori->request($request);

        $rateIds = array_map(fn (AnimeRatesResponse $r) => $r->id, $ratesData);
        $shikimoriRates = $this->rates->findByUserOrShikimoriIdsIndexedByShikimoriId($user, $rateIds);
        $appRates = $this->findAppRatesIndexedByAnimeId($user);
        foreach ($ratesData as $rateData) {
            $anime = $this->animes->findOrNew($rateData->anime->id);
            $anime->updateFromBaseData($rateData->anime);
            $this->entityManager->persist($anime);

            if (array_key_exists($rateData->id, $shikimoriRates)) {
                $rate = $shikimoriRates[$rateData->id];
                unset($shikimoriRates[$rateData->id]);
            } elseif (array_key_exists($rateData->anime->id, $appRates)) {
                $rate = $appRates[$rateData->anime->id];
            } else {
                $rate = new AnimeRate();
            }
            $this->setRateData($rate, $rateData, $user, $anime);
            $this->entityManager->persist($rate);
        }

        foreach ($shikimoriRates as $rate) {
            $this->entityManager->remove($rate);
        }

        $sync->setState(UserSyncState::SERIES);
        $this->entityManager->persist($sync);

        $this->entityManager->flush();

        $this->bus->dispatch(new SyncUserSeriesMessage($user->getId()));
    }

    /**
     * @return array<int, AnimeRate>
     */
    private function findAppRatesIndexedByAnimeId(User $user): array
    {
        $rates = $this->rates->findBy(['user' => $user, 'status' => AnimeRateStatus::SKIPPED]);
        $result = [];
        foreach ($rates as $rate) {
            $result[$rate->getAnime()->getId()] = $rate;
        }

        return $result;
    }

    private function setRateData(AnimeRate $rate, AnimeRatesResponse $data, User $user, Anime $anime): void
    {
        $rate->setUser($user);
        $rate->setShikimoriId($data->id);
        $rate->setAnime($anime);
        $rate->setScore($data->score);
        $rate->setStatus(AnimeRateStatus::fromUserAnimeStatus($data->status));
    }
}
