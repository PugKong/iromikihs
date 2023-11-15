<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\UserSyncState;
use App\Message\SyncUserAnimeRatesMessage;
use App\Tests\Factory\UserFactory;
use App\Tests\Twig\Component\CsrfTokenManagerSpy;
use App\Twig\Component\SimpleForm;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
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

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', $fromUrl)
        ;
        self::getClient()->submitForm('Sync');
        self::assertResponseRedirects('http://localhost'.$fromUrl);

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

        self::getClient()
            ->loginUser($user->object())
            ->request('POST', '/sync')
        ;
        self::assertResponseIsUnprocessable('Invalid csrf token');

        self::getClient()->request('POST', '/sync', [SimpleForm::CSRF_TOKEN_FIELD => '123']);
        self::assertResponseIsUnprocessable('Invalid csrf token');
    }

    /**
     * @dataProvider startChecksSyncStateInProgressProvider
     */
    public function testStartChecksSyncState(?UserSyncState $state, bool $allowed): void
    {
        $user = UserFactory::new()->withLinkedAccount()->withSyncStatus(state: $state)->create();

        self::getClient()->getContainer()->set(
            CsrfTokenManagerInterface::class,
            new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']),
        );
        self::getClient()
            ->loginUser($user->object())
            ->request('POST', '/sync', [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken])
        ;

        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

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

        self::getClient()->getContainer()->set(
            CsrfTokenManagerInterface::class,
            new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']),
        );
        self::getClient()
            ->loginUser($user->object())
            ->request('POST', '/sync', [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken])
        ;

        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

        self::assertHasFlashError('Link account to start syncing.');
    }
}
