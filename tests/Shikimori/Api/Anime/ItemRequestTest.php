<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Api\Anime;

use App\Shikimori\Api\Anime\ItemRequest;
use App\Shikimori\Api\Anime\ItemResponse;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Tests\Shikimori\ShikimoriTestCase;

final class ItemRequestTest extends ShikimoriTestCase
{
    public function testRequest(): void
    {
        $request = new ItemRequest($token = 'the_token', $animeId = 1234);
        $expected = new ItemResponse(
            id: $id = 1235,
            name: $name = 'Related name',
            url: $url = '/animes/related',
            kind: $kind = Kind::MOVIE,
            status: $status = Status::ONGOING,
        );
        $response = [
            'id' => $id,
            'name' => $name,
            'url' => $url,
            'kind' => $kind,
            'status' => $status,
        ];

        $result = self::request($request, $response);

        self::assertRoute('GET', "/api/animes/$animeId");
        self::assertUserAgent();
        self::assertAuthorization($token);

        self::assertEquals($expected, $result);
    }
}
