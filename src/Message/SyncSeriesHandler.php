<?php

declare(strict_types=1);

namespace App\Message;

use App\Repository\AnimeRateRepository;
use App\Repository\UserRepository;
use App\Service\Anime\SyncAnimeSeries;
use App\Service\Shikimori\AnimeSeriesFetcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class SyncSeriesHandler
{
    private UserRepository $users;
    private AnimeRateRepository $rates;
    private AnimeSeriesFetcher $seriesFetcher;
    private SyncAnimeSeries $syncAnimeSeries;
    private MessageBusInterface $bus;

    public function __construct(
        UserRepository $users,
        AnimeRateRepository $rates,
        AnimeSeriesFetcher $seriesFetcher,
        SyncAnimeSeries $syncAnimeSeries,
        MessageBusInterface $bus,
    ) {
        $this->users = $users;
        $this->rates = $rates;
        $this->seriesFetcher = $seriesFetcher;
        $this->syncAnimeSeries = $syncAnimeSeries;
        $this->bus = $bus;
    }

    public function __invoke(SyncSeries $message): void
    {
        $user = $this->users->find($message->userId);
        if (null === $user) {
            return;
        }

        $anime = $this->rates->findNextAnimeToSyncSeriesByUser($user);
        if (null === $anime) {
            return;
        }

        $animes = ($this->seriesFetcher)($user, $anime->getId());
        ($this->syncAnimeSeries)($animes);

        $this->bus->dispatch($message);
    }
}
