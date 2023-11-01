<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Client;

use App\Tests\Shikimori\Client\RequestStub\AuthenticatedRequestStub;
use App\Tests\Shikimori\Client\RequestStub\FormRequestStub;
use App\Tests\Shikimori\Client\RequestStub\ListRequestStub;
use App\Tests\Shikimori\Client\RequestStub\RequestStub;
use App\Tests\Shikimori\Client\RequestStub\ResponseStub;
use App\Tests\Shikimori\ShikimoriTestCase;
use App\Tests\TestDouble\Shikimori\RateLimiterSpy;
use App\Tests\Trait\GetService;

final class ShikimoriHttpTest extends ShikimoriTestCase
{
    use GetService;

    public function testRequest(): void
    {
        $request = new RequestStub('GET', '/api/simple');
        $response = ['id' => 6610];

        $result = self::request($request, $response);

        self::assertRoute('GET', '/api/simple');
        self::assertUserAgent();
        self::assertNoAuthorization();

        self::assertEquals(
            new ResponseStub(id: 6610),
            $result,
        );
    }

    public function testRequestRespectsRateLimits(): void
    {
        $request = new RequestStub('GET', '/api/simple');
        $response = ['id' => 6610];

        self::request($request, $response);

        self::getService(RateLimiterSpy::class)->assertWaited(1);
    }

    public function testRequestAuthenticated(): void
    {
        $request = new AuthenticatedRequestStub('GET', '/api/authenticated');
        $response = ['id' => 6610];

        $result = self::request($request, $response);

        self::assertRoute('GET', '/api/authenticated');
        self::assertUserAgent();
        self::assertAuthorization(AuthenticatedRequestStub::TOKEN);

        self::assertEquals(
            new ResponseStub(id: 6610),
            $result,
        );
    }

    public function testFormRequest(): void
    {
        $request = new FormRequestStub('POST', '/api/form');
        $response = ['id' => 6610];

        $result = self::request($request, $response);

        self::assertRoute('POST', '/api/form');
        self::assertUserAgent();
        self::assertNoAuthorization();

        self::assertFormRequest();
        self::assertFormData(['foo' => 'bar']);

        self::assertEquals(
            new ResponseStub(id: 6610),
            $result,
        );
    }

    public function testListRequest(): void
    {
        $request = new ListRequestStub('GET', '/api/list');
        $response = [['id' => 6610], ['id' => 6611]];

        $result = self::request($request, $response);

        self::assertRoute('GET', '/api/list');
        self::assertUserAgent();
        self::assertNoAuthorization();

        self::assertEquals(
            [new ResponseStub(id: 6610), new ResponseStub(id: 6611)],
            $result,
        );
    }
}
