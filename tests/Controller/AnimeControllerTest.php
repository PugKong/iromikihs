<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Message\SyncList;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\UserFactory;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class AnimeControllerTest extends ControllerTestCase
{
    use InteractsWithMessenger;

    /**
     * @dataProvider requiresAuthenticationProvider
     */
    public function testRequiresAuthentication(string $method, string $uri): void
    {
        self::assertRequiresAuthentication($method, $uri);
    }

    public static function requiresAuthenticationProvider(): array
    {
        return [
            ['GET', '/'],
            ['POST', '/sync'],
        ];
    }

    public function testIndex(): void
    {
        $user = UserFactory::new()->withLinkedAccount()->create();

        $anime = AnimeFactory::createOne([
            'name' => $animeName = 'The anime',
            'kind' => $animeKind = Kind::MOVIE,
            'status' => $animeStatus = Status::RELEASED,
        ]);
        AnimeRateFactory::createOne([
            'user' => $user,
            'anime' => $anime,
            'status' => $rateProgress = UserAnimeStatus::WATCHING,
            'score' => $rateScore = 9,
        ]);

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', '/')
        ;

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Anime list');

        self::assertHasNoAccountLinkSection();
        self::assertHasButton('Sync list');

        self::assertTable(
            'table.anime-list',
            [['Name', 'Kind', 'Status', 'Progress', 'Score']],
            [[$animeName, $animeKind->value, $animeStatus->value, $rateProgress->value, (string) $rateScore]],
        );
    }

    public function testIndexProfileNotLinked(): void
    {
        self::getClient()
            ->loginUser(UserFactory::createOne()->object())
            ->request('GET', '/')
        ;
        self::assertResponseIsSuccessful();

        self::assertHasAccountLinkSection();
        self::assertHasNoButton('Sync list');
    }

    public function testIndexQueryCount(): void
    {
        $user = UserFactory::createOne();
        AnimeRateFactory::createMany($rates = 10, ['user' => $user]);

        self::enableProfiler();
        self::getClient()
            ->loginUser($user->object())
            ->request('GET', '/')
        ;
        self::assertResponseIsSuccessful();

        self::assertTableRowsCount('table.anime-list', $rates);

        self::assertSame(2, self::dbCollector()->getQueryCount());
    }

    public function testSync(): void
    {
        $user = UserFactory::new()->withLinkedAccount()->create();

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', '/')
        ;
        self::assertResponseIsSuccessful();

        self::getClient()->submitForm('Sync list');
        self::assertResponseRedirects('/');

        $messages = $this->transport('async')->queue()->messages(SyncList::class);
        self::assertCount(1, $messages);
        self::assertTrue($user->getId()->equals($messages[0]->userId));

        self::getClient()->followRedirect();
        self::assertSelectorTextSame('.flash-notice', 'Your list will be synced soon.');
    }
}
