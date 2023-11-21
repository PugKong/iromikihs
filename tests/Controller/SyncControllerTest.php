<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\Controller;
use App\Entity\UserSyncState;
use App\Message\SyncUserAnimeRatesMessage;
use App\Tests\Factory\UserFactory;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class SyncControllerTest extends ControllerTestCase
{
    use InteractsWithMessenger;

    public function testStartRequiresAuthentication(): void
    {
        self::assertRequiresAuthentication('POST', '/sync');
    }

    /**
     * @dataProvider startProvider
     */
    public function testStart(string $fromUrl): void
    {
        $user = UserFactory::new()->withLinkedAccount()->create();

        $client = self::getClient();
        $client->loginUser($user->object());
        $client->request('GET', $fromUrl);
        $client->submitForm('Sync');
        self::assertResponseRedirects($fromUrl);

        self::assertSame(UserSyncState::ANIME_RATES, $user->getSync()->getState());

        $messages = $this->transport('async')->queue()->messages(SyncUserAnimeRatesMessage::class);
        self::assertCount(1, $messages);
        self::assertEquals($user->getId(), $messages[0]->userId);
    }

    public static function startProvider(): array
    {
        return [
            ['/'],
            ['/series/incomplete'],
            ['/series/complete'],
            ['/series/dropped'],
            ['/profile'],
        ];
    }

    public function testStartChecksCsrfToken(): void
    {
        $user = UserFactory::new()->withLinkedAccount()->create();

        $client = self::getClient();
        $client->loginUser($user->object());
        $client->request('POST', '/sync');
        self::assertResponseIsUnprocessable('Invalid csrf token');

        $client->request('POST', '/sync', [Controller::COMMON_CSRF_TOKEN_FIELD => '123']);
        self::assertResponseIsUnprocessable('Invalid csrf token');
    }

    /**
     * @dataProvider startChecksSyncStateInProgressProvider
     */
    public function testStartChecksSyncState(?UserSyncState $state, bool $allowed): void
    {
        $user = UserFactory::new()->withLinkedAccount()->withSyncStatus(state: $state)->create();

        $client = self::getClient();
        $client->loginUser($user->object());
        self::requestWithCsrfToken('POST', '/sync');

        self::assertResponseRedirects('/');
        $client->followRedirect();

        if ($allowed) {
            self::assertHasNoFlashError('Sync is already in process.');
        } else {
            self::assertHasFlashError('Sync is already in process.');
        }
    }

    public static function startChecksSyncStateInProgressProvider(): array
    {
        return [
            [null, true],
            [UserSyncState::FAILED, true],
            [UserSyncState::ANIME_RATES, false],
            [UserSyncState::SERIES, false],
            [UserSyncState::SERIES_RATES, false],
        ];
    }

    public function testStartChecksAccountLinkState(): void
    {
        $user = UserFactory::createOne();

        $client = self::getClient();
        $client->loginUser($user->object());
        self::requestWithCsrfToken('POST', '/sync');

        self::assertResponseRedirects('/');
        $client->followRedirect();

        self::assertHasFlashError('Link account to start syncing.');
    }
}
