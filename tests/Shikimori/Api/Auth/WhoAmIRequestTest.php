<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Api\Auth;

use App\Shikimori\Api\Auth\WhoAmIRequest;
use App\Shikimori\Api\Auth\WhoAmIResponse;
use App\Tests\Shikimori\ShikimoriTestCase;

final class WhoAmIRequestTest extends ShikimoriTestCase
{
    public function testRequest(): void
    {
        $request = new WhoAmIRequest($accessToken = 'the_access_token');
        $response = ['id' => 1];

        $result = self::request($request, $response);

        self::assertRoute('GET', '/api/users/whoami');
        self::assertUserAgent();
        self::assertAuthorization($accessToken);

        self::assertEquals(new WhoAmIResponse(id: 1), $result);
    }
}
