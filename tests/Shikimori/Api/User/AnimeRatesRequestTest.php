<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Api\User;

use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Shikimori\Api\User\AnimeRatesRequest;
use App\Shikimori\Api\User\AnimeRatesResponse;
use App\Shikimori\Api\User\AnimeRatesResponseAnimeItem;
use App\Tests\Shikimori\ShikimoriTestCase;
use App\Tests\Trait\BaseAnimeDataUtil;

final class AnimeRatesRequestTest extends ShikimoriTestCase
{
    use BaseAnimeDataUtil;

    public function testRequest(): void
    {
        $request = new AnimeRatesRequest($token = 'a_token', 6610);
        $expected = [
            new AnimeRatesResponse(
                id: 100,
                score: 6,
                status: UserAnimeStatus::COMPLETED,
                anime: self::createAnimeItem(AnimeRatesResponseAnimeItem::class, 1),
            ),
            new AnimeRatesResponse(
                id: 200,
                score: 7,
                status: UserAnimeStatus::WATCHING,
                anime: self::createAnimeItem(AnimeRatesResponseAnimeItem::class, 2),
            ),
        ];
        $response = [
            [
                'id' => $expected[0]->id,
                'score' => $expected[0]->score,
                'status' => $expected[0]->status->value,
                'anime' => [
                    'id' => $expected[0]->anime->id,
                    'name' => $expected[0]->anime->name,
                    'url' => $expected[0]->anime->url,
                    'kind' => $expected[0]->anime->kind?->value,
                    'status' => $expected[0]->anime->status->value,
                ],
            ],
            [
                'id' => $expected[1]->id,
                'score' => $expected[1]->score,
                'status' => $expected[1]->status->value,
                'anime' => [
                    'id' => $expected[1]->anime->id,
                    'name' => $expected[1]->anime->name,
                    'url' => $expected[1]->anime->url,
                    'kind' => $expected[1]->anime->kind?->value,
                    'status' => $expected[1]->anime->status->value,
                ],
            ],
        ];

        $result = self::request($request, $response);

        self::assertRoute('GET', '/api/users/6610/anime_rates?limit=5000');
        self::assertUserAgent();
        self::assertAuthorization($token);

        self::assertEquals($expected, $result);
    }

    public function testRequestFilterByStatus(): void
    {
        $request = new AnimeRatesRequest($token = 'the_token', 6611, UserAnimeStatus::COMPLETED);
        $response = [];

        $result = self::request($request, $response);

        self::assertRoute('GET', '/api/users/6611/anime_rates?limit=5000&status=completed');
        self::assertUserAgent();
        self::assertAuthorization($token);

        self::assertEquals([], $result);
    }
}
