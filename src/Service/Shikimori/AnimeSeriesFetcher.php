<?php

declare(strict_types=1);

namespace App\Service\Shikimori;

use App\Entity\User;
use App\Shikimori\Api\Anime\ItemRequest;
use App\Shikimori\Api\Anime\RelatedRequest;
use App\Shikimori\Client\Shikimori;

use function array_key_exists;
use function count;
use function in_array;

final readonly class AnimeSeriesFetcher
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

    public function __invoke(User $user, int $id): AnimeSeriesFetcherResult
    {
        $itemRequest = new ItemRequest($this->tokens->retrieve($user), $id);
        $item = $this->shikimori->request($itemRequest);

        $seriesName = $item->name;
        $items = [$id => $item];
        $queue = [$item];
        while (count($queue) > 0) {
            $item = array_shift($queue);
            $hasPrequel = false;

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

                if (self::PREQUEL === $relatedItem->relation) {
                    $hasPrequel = true;
                }

                if (array_key_exists($anime->id, $items)) {
                    continue;
                }

                $items[$anime->id] = $anime;
                $queue[] = $anime;
            }

            if (!$hasPrequel) {
                $seriesName = $item->name;
            }
        }

        return new AnimeSeriesFetcherResult($seriesName, array_values($items));
    }
}
