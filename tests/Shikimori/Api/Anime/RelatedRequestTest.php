<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Api\Anime;

use App\Shikimori\Api\Anime\RelatedRequest;
use App\Shikimori\Api\Anime\RelatedResponse;
use App\Shikimori\Api\Anime\RelatedResponseAnimeItem;
use App\Tests\Shikimori\ShikimoriTestCase;
use App\Tests\Trait\BaseAnimeDataUtil;

final class RelatedRequestTest extends ShikimoriTestCase
{
    use BaseAnimeDataUtil;

    public function testRequest(): void
    {
        $request = new RelatedRequest($token = 'the_token', $animeId = 1234);
        $expected = [
            new RelatedResponse(
                relation: $relation = 'Adaptation',
                anime: $related = self::createAnimeItem(RelatedResponseAnimeItem::class, 1234),
            ),
        ];
        $response = [
            [
                'relation' => $relation,
                'anime' => [
                    'id' => $related->id,
                    'name' => $related->name,
                    'url' => $related->url,
                    'kind' => $related->kind,
                    'status' => $related->status,
                ],
            ],
        ];

        $result = self::request($request, $response);

        self::assertRoute('GET', "/api/animes/$animeId/related");
        self::assertUserAgent();
        self::assertAuthorization($token);

        self::assertEquals($expected, $result);
    }
}
