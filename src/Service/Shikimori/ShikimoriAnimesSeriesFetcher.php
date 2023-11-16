<?php

declare(strict_types=1);

namespace App\Service\Shikimori;

use App\Entity\User;
use App\Service\Series\NameGpt;
use App\Shikimori\Api\Anime\ItemRequest;
use App\Shikimori\Api\Anime\RelatedRequest;
use App\Shikimori\Client\Shikimori;

use function array_key_exists;
use function count;
use function in_array;

final readonly class ShikimoriAnimesSeriesFetcher implements AnimeSeriesFetcher
{
    public const PREQUEL = 'Prequel';
    public const SEQUEL = 'Sequel';

    private TokenStorage $tokens;
    private Shikimori $shikimori;
    private NameGpt $seriesNameGpt;

    public function __construct(TokenStorage $tokens, Shikimori $shikimori, NameGpt $seriesNameGpt)
    {
        $this->tokens = $tokens;
        $this->shikimori = $shikimori;
        $this->seriesNameGpt = $seriesNameGpt;
    }

    public function __invoke(User $user, int $animeId): AnimeSeriesFetcherResult
    {
        $itemRequest = new ItemRequest($this->tokens->retrieve($user), $animeId);
        $item = $this->shikimori->request($itemRequest);

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

                $items[$anime->id] = $anime;
                $queue[] = $anime;
            }
        }

        $items = array_values($items);
        $seriesName = ($this->seriesNameGpt)($items);

        return new AnimeSeriesFetcherResult($seriesName, $items);
    }
}
