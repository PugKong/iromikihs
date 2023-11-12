<?php

declare(strict_types=1);

namespace App\Service\Shikimori;

use App\Entity\User;
use App\Shikimori\Api\Anime\ItemRequest;
use App\Shikimori\Api\Anime\RelatedRequest;
use App\Shikimori\Api\BaseAnimeData;
use App\Shikimori\Client\Shikimori;
use DateTimeImmutable;

use function array_key_exists;
use function count;
use function in_array;

final readonly class ShikimoriAnimesSeriesFetcher implements AnimeSeriesFetcher
{
    public const PREQUEL = 'Prequel';
    public const SEQUEL = 'Sequel';

    private TokenStorage $tokens;
    private Shikimori $shikimori;

    public function __construct(TokenStorage $tokens, Shikimori $shikimori)
    {
        $this->tokens = $tokens;
        $this->shikimori = $shikimori;
    }

    public function __invoke(User $user, int $animeId): AnimeSeriesFetcherResult
    {
        $itemRequest = new ItemRequest($this->tokens->retrieve($user), $animeId);
        $item = $this->shikimori->request($itemRequest);

        $seriesName = $item->name;
        $seriesNameDate = $this->selectDate($item);

        $items = [$animeId => $item];
        $queue = [$item];
        while (count($queue) > 0) {
            $item = array_shift($queue);

            $relatedRequest = new RelatedRequest($this->tokens->retrieve($user), $item->id);
            $relatedItems = $this->shikimori->request($relatedRequest);
            foreach ($relatedItems as $relatedItem) {
                $anime = $relatedItem->anime;
                if (null === $anime) {
                    continue;
                }

                if (!in_array($relatedItem->relation, [self::PREQUEL, self::SEQUEL])) {
                    continue;
                }

                if (array_key_exists($anime->id, $items)) {
                    continue;
                }

                $date = $this->selectDate($anime);
                if ($date < $seriesNameDate) {
                    $seriesName = $anime->name;
                }

                $items[$anime->id] = $anime;
                $queue[] = $anime;
            }
        }

        return new AnimeSeriesFetcherResult($seriesName, array_values($items));
    }

    private function selectDate(BaseAnimeData $data): DateTimeImmutable
    {
        if (null !== $data->airedOn) {
            return $data->airedOn;
        }

        if (null !== $data->releasedOn) {
            return $data->releasedOn;
        }

        return new DateTimeImmutable('tomorrow');
    }
}
