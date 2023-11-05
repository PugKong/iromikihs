<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Api\User;

use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Shikimori\Api\User\AnimeItem;
use App\Shikimori\Api\User\AnimeRatesRequest;
use App\Shikimori\Api\User\AnimeRatesResponse;
use App\Tests\Shikimori\ShikimoriTestCase;

final class AnimeRatesRequestTest extends ShikimoriTestCase
{
    public function testRequest(): void
    {
        $request = new AnimeRatesRequest($token = 'a_token', 6610);
        $expected = [
            new AnimeRatesResponse(
                id: 100,
                score: 6,
                status: UserAnimeStatus::COMPLETED,
                anime: new AnimeItem(
                    id: 1,
                    name: 'anime 1',
                    url: '/animes/1',
                    kind: Kind::TV,
                    status: Status::RELEASED,
                ),
            ),
            new AnimeRatesResponse(
                id: 200,
                score: 7,
                status: UserAnimeStatus::WATCHING,
                anime: new AnimeItem(
                    id: 2,
                    name: 'anime 2',
                    url: '/animes/2',
                    kind: Kind::TV,
                    status: Status::ONGOING,
                ),
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
                    'kind' => $expected[0]->anime->kind->value,
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
                    'kind' => $expected[1]->anime->kind->value,
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
