<?php

declare(strict_types=1);

namespace App\Tests\Twig\Component;

use App\Entity\UserSyncState;
use App\Tests\Factory\UserFactory;
use App\Twig\Component\SimpleForm;
use App\Twig\Component\SyncStatus;
use DateTimeImmutable;

final class SyncStatusTest extends ComponentTestCase
{
    private const COMPONENT_NAME = 'SyncStatus';

    protected function setUp(): void
    {
        parent::setUp();

        $csrfTokenManagerSpy = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => '123']);
        $csrfTokenManagerSpy->register(self::getContainer());
    }

    public function testComponentMount(): void
    {
        $user = UserFactory::createOne();

        $component = $this->mountTwigComponent(self::COMPONENT_NAME, ['user' => $user->object()]);
        self::assertInstanceOf(SyncStatus::class, $component);
        self::assertSame($user->object(), $component->getUser());
        self::assertSame($user->getSync(), $component->getSync());
    }

    public function testComponentRendersLinkAccount(): void
    {
        $user = UserFactory::createOne();

        $rendered = $this->renderTwigComponent(self::COMPONENT_NAME, ['user' => $user->object()]);

        $section = $rendered->crawler()->filter('section.sync-status');
        self::assertCount(1, $section);
        self::assertSame('Shikimori account is not linked. Link your account to enable sync.', $section->text());

        $accountLinkButton = $section->selectLink('Link your account');
        self::assertCount(1, $accountLinkButton);
        self::assertSame('/profile/link/start', $accountLinkButton->attr('href'));
    }

    public function testComponentRendersLinkingAccount(): void
    {
        $user = UserFactory::new()->withSyncStatus(state: UserSyncState::LINK_ACCOUNT)->create();

        $rendered = $this->renderTwigComponent(self::COMPONENT_NAME, ['user' => $user->object()]);

        $section = $rendered->crawler()->filter('section.sync-status');
        self::assertCount(1, $section);
        self::assertSame('Linking account.', $section->text());
    }

    public function testComponentRendersSyncDataFirstTime(): void
    {
        $user = UserFactory::new()->withLinkedAccount()->create();

        $rendered = $this->renderTwigComponent(self::COMPONENT_NAME, ['user' => $user->object()]);

        $section = $rendered->crawler()->filter('section.sync-status');
        self::assertCount(1, $section);
        self::assertSame('Data was never synced. Sync it now.', $section->text());

        $syncButton = $section->selectButton('Sync');
        self::assertCount(1, $syncButton);
        self::assertSame('/sync', $syncButton->ancestors()->attr('action'));
    }

    public function testComponentRendersSyncData(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(syncedAt: new DateTimeImmutable('2007-01-02 03:04:05'))
            ->create()
        ;

        $rendered = $this->renderTwigComponent(self::COMPONENT_NAME, ['user' => $user->object()]);

        $section = $rendered->crawler()->filter('section.sync-status');
        self::assertCount(1, $section);
        self::assertSame('Data synced at 2007-01-02. Sync it now.', $section->text());

        $syncButton = $section->selectButton('Sync');
        self::assertCount(1, $syncButton);
        self::assertSame('/sync', $syncButton->ancestors()->attr('action'));
    }

    public function testComponentRendersSyncingList(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::ANIME_RATES)
            ->create()
        ;

        $rendered = $this->renderTwigComponent(self::COMPONENT_NAME, ['user' => $user->object()]);

        $section = $rendered->crawler()->filter('section.sync-status');
        self::assertCount(1, $section);
        self::assertSame('1/3 Syncing anime list.', $section->text());
    }

    public function testComponentRendersSyncingSeriesData(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES)
            ->create()
        ;

        $rendered = $this->renderTwigComponent(self::COMPONENT_NAME, ['user' => $user->object()]);

        $section = $rendered->crawler()->filter('section.sync-status');
        self::assertCount(1, $section);
        self::assertSame('2/3 Syncing series data.', $section->text());
    }

    public function testComponentRendersSyncingSeriesState(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::SERIES_RATES)
            ->create()
        ;

        $rendered = $this->renderTwigComponent(self::COMPONENT_NAME, ['user' => $user->object()]);

        $section = $rendered->crawler()->filter('section.sync-status');
        self::assertCount(1, $section);
        self::assertSame('3/3 Syncing series states.', $section->text());
    }

    public function testComponentRendersFailed(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: UserSyncState::FAILED)
            ->create()
        ;

        $rendered = $this->renderTwigComponent(self::COMPONENT_NAME, ['user' => $user->object()]);

        $section = $rendered->crawler()->filter('section.sync-status');
        self::assertCount(1, $section);
        self::assertSame('Last data sync failed. Try to Sync it again.', $section->text());

        $syncButton = $section->selectButton('Sync');
        self::assertCount(1, $syncButton);
        self::assertSame('/sync', $syncButton->ancestors()->attr('action'));
    }

    public function testComponentRendersFailedLinkAccount(): void
    {
        $user = UserFactory::new()
            ->withSyncStatus(state: UserSyncState::FAILED)
            ->create()
        ;

        $rendered = $this->renderTwigComponent(self::COMPONENT_NAME, ['user' => $user->object()]);

        $section = $rendered->crawler()->filter('section.sync-status');
        self::assertCount(1, $section);
        self::assertSame('Failed to link account. Try to link it again.', $section->text());

        $accountLinkButton = $section->selectLink('link it');
        self::assertCount(1, $accountLinkButton);
        self::assertSame('/profile/link/start', $accountLinkButton->attr('href'));
    }
}
