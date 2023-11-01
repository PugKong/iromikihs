<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Api\Auth;

use App\Shikimori\Api\Auth\ExchangeCodeRequest;
use App\Shikimori\Api\Auth\TokenResponse;
use App\Tests\Shikimori\ShikimoriTestCase;

final class ExchangeCodeRequestTest extends ShikimoriTestCase
{
    public function testRequest(): void
    {
        $request = new ExchangeCodeRequest($code = '6612');
        $response = [
            'access_token' => $accessToken = 'access_token',
            'refresh_token' => $refreshToken = 'refresh_token',
            'created_at' => $createdAt = 6610,
            'expires_in' => $expiresIn = 6611,
        ];

        $result = self::request($request, $response);

        self::assertRoute('POST', '/oauth/token');
        self::assertUserAgent();
        self::assertNoAuthorization();

        self::assertFormRequest();
        self::assertFormData([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => 'shikimori_client_id',
            'client_secret' => 'shikimori_client_secret',
            'redirect_uri' => 'http://localhost/profile/link',
        ]);

        self::assertEquals(
            new TokenResponse(
                accessToken: $accessToken,
                refreshToken: $refreshToken,
                createdAt: $createdAt,
                expiresIn: $expiresIn,
            ),
            $result,
        );
    }
}
