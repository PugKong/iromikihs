<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Api\Auth;

use App\Shikimori\Api\Auth\RefreshTokenRequest;
use App\Shikimori\Api\Auth\TokenResponse;
use App\Tests\Shikimori\ShikimoriTestCase;

final class RefreshTokenRequestTest extends ShikimoriTestCase
{
    public function testRequest(): void
    {
        $request = new RefreshTokenRequest($refreshToken = 'old refresh');
        $response = [
            'access_token' => $newAccessToken = 'access_token',
            'refresh_token' => $newRefreshToken = 'refresh_token',
            'created_at' => $createdAt = 6610,
            'expires_in' => $expiresIn = 6611,
        ];

        $result = self::request($request, $response);

        self::assertRoute('POST', '/oauth/token');
        self::assertUserAgent();
        self::assertNoAuthorization();

        self::assertFormRequest();
        self::assertFormData([
            'grant_type' => 'refresh_token',
            'client_id' => 'shikimori_client_id',
            'client_secret' => 'shikimori_client_secret',
            'refresh_token' => $refreshToken,
        ]);

        self::assertEquals(
            new TokenResponse(
                accessToken: $newAccessToken,
                refreshToken: $newRefreshToken,
                createdAt: $createdAt,
                expiresIn: $expiresIn,
            ),
            $result,
        );
    }
}
