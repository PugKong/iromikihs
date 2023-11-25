<?php

declare(strict_types=1);

namespace App\Tests\Twig\Component;

use App\Controller\Controller;
use App\Entity\AnimeRateStatus;
use App\Entity\SeriesState;
use App\Entity\User;
use App\Service\Anime\GetUserSeriesList\AnimeResult;
use App\Service\Anime\GetUserSeriesList\SeriesResult;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Tests\TestDouble\CsrfTokenManagerSpy;
use App\Twig\Component\SeriesList;
use Symfony\Component\DomCrawler\Crawler;

final class SeriesListTest extends ComponentTestCase
{
    private const COMPONENT_NAME = 'SeriesList';
    private const CSRF_TOKEN_VALUE = '123';

    private CsrfTokenManagerSpy $csrfTokenManagerSpy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csrfTokenManagerSpy = new CsrfTokenManagerSpy([
            Controller::COMMON_CSRF_TOKEN_ID => self::CSRF_TOKEN_VALUE,
        ]);
        $this->csrfTokenManagerSpy->register(self::getContainer());
    }

    public function testComponentMount(): void
    {
        $component = $this->mountTwigComponent(self::COMPONENT_NAME, [
            'user' => $user = new User(),
            'series' => $series = [],
        ]);

        self::assertInstanceOf(SeriesList::class, $component);
        self::assertSame($user, $component->getUser());
        self::assertSame($series, $component->getSeries());
    }

    public function testComponentRendersNoData(): void
    {
        $rendered = $this->renderTwigComponent(
            self::COMPONENT_NAME,
            ['user' => new User(), 'series' => []],
        );

        self::assertCount(1, $crawler = $rendered->crawler());
        self::assertCount(1, $section = $crawler->filter('section.series-list'));
        self::assertSame('No series found.', $section->text());
    }

    public function testComponentRendersSeriesNames(): void
    {
        $rendered = $this->renderTwigComponent(
            self::COMPONENT_NAME,
            [
                'user' => new User(),
                'series' => [
                    self::createSeriesResult(name: $firstSeriesName = 'a series name'),
                    self::createSeriesResult(name: $secondSeriesName = 'the series name'),
                ],
            ],
        );

        self::assertCount(1, $crawler = $rendered->crawler());
        self::assertCount(1, $section = $crawler->filter('section.series-list'));
        self::assertCount(2, $items = $section->filter('div.series-list-item'));
        self::assertCount(2, $headers = $items->filter('h2.font-bold'));
        self::assertSame([$firstSeriesName, $secondSeriesName], $headers->each(fn (Crawler $c) => $c->text()));
    }

    public function testComponentRendersSeriesControls(): void
    {
        $rendered = $this->renderTwigComponent(
            self::COMPONENT_NAME,
            [
                'user' => new User(),
                'series' => [
                    self::createSeriesResult(score: 0),
                    self::createSeriesResult(state: SeriesState::DROPPED, score: 3.3334),
                ],
            ],
        );

        self::assertCount(1, $crawler = $rendered->crawler());
        self::assertCount(1, $section = $crawler->filter('section.series-list'));
        self::assertCount(2, $items = $section->filter('div.series-list-item'));
        self::assertCount(2, $formControls = $items->filter('form.series-controls'));
        self::assertSame(
            ['0', '3.33'],
            $formControls->filter('span.btn')->each(fn (Crawler $c) => $c->text()),
        );
        self::assertSame(
            ['Drop series', 'Restore series'],
            $formControls->filter('button.btn')->each(fn (Crawler $c) => $c->text()),
        );
    }

    public function testComponentsRendersSeriesItems(): void
    {
        $rendered = $this->renderTwigComponent(
            self::COMPONENT_NAME,
            [
                'user' => new User(),
                'series' => [
                    self::createSeriesResult(animes: [
                        self::createAnimeResult(
                            name: 'a name',
                        ),
                        self::createAnimeResult(
                            kind: Kind::TV,
                            status: Status::RELEASED,
                            name: 'the name',
                            state: AnimeRateStatus::DROPPED,
                            score: 5,
                        ),
                    ]),
                ],
            ],
        );

        self::assertCount(1, $crawler = $rendered->crawler());
        self::assertCount(1, $section = $crawler->filter('section.series-list'));
        self::assertCount(1, $seriesItems = $section->filter('div.series-list-item'));
        self::assertCount(1, $animesTable = $seriesItems->filter('table.table'));
        self::assertTable(
            $animesTable,
            [['Name', 'Kind', 'Status', 'Progress', 'Score', 'Action']],
            [
                ['a name', '—', 'anons', '—', '—', 'Skip'],
                ['the name', 'tv', 'released', 'dropped', '5', '—'],
            ],
        );
    }

    public function testRendersAnimeLink(): void
    {
        $rendered = $this->renderTwigComponent(
            self::COMPONENT_NAME,
            [
                'user' => new User(),
                'series' => [
                    self::createSeriesResult(animes: [
                        self::createAnimeResult(
                            name: $animeName = 'the name',
                            url: '/animes/100',
                        ),
                    ]),
                ],
            ],
        );

        self::assertCount(1, $crawler = $rendered->crawler());
        self::assertCount(1, $section = $crawler->filter('section.series-list'));
        self::assertCount(1, $seriesItems = $section->filter('div.series-list-item'));
        self::assertCount(1, $animesTable = $seriesItems->filter('table.table'));
        self::assertCount(1, $animeLink = $animesTable->selectLink($animeName));
        self::assertSame('https://shikimori.example.com/animes/100', $animeLink->attr('href'));
    }

    public function testRendersDropSeriesForm(): void
    {
        $rendered = $this->renderTwigComponent(
            self::COMPONENT_NAME,
            [
                'user' => new User(),
                'series' => [self::createSeriesResult(seriesRateId: $seriesRateId = 'rate_id')],
            ],
        );

        self::assertCount(1, $crawler = $rendered->crawler());
        self::assertCount(1, $section = $crawler->filter('section.series-list'));
        self::assertCount(1, $items = $section->filter('div.series-list-item'));
        self::assertCount(1, $button = $items->selectButton('Drop series'));

        $form = $button->ancestors();
        $this->csrfTokenManagerSpy->assertCalls(1);
        self::assertSame("/series/rates/$seriesRateId/drop", $form->attr('action'));
        self::assertSame(self::CSRF_TOKEN_VALUE, $form->filter('input[type=hidden]')->attr('value'));
    }

    public function testRendersRestoreSeriesForm(): void
    {
        $rendered = $this->renderTwigComponent(
            self::COMPONENT_NAME,
            [
                'user' => new User(),
                'series' => [
                    self::createSeriesResult(
                        seriesRateId: $seriesRateId = 'another_rate_id',
                        state: SeriesState::DROPPED,
                    ),
                ],
            ],
        );

        self::assertCount(1, $crawler = $rendered->crawler());
        self::assertCount(1, $section = $crawler->filter('section.series-list'));
        self::assertCount(1, $items = $section->filter('div.series-list-item'));
        self::assertCount(1, $button = $items->selectButton('Restore series'));

        $form = $button->ancestors();
        $this->csrfTokenManagerSpy->assertCalls(1);
        self::assertSame("/series/rates/$seriesRateId/restore", $form->attr('action'));
        self::assertSame(self::CSRF_TOKEN_VALUE, $form->filter('input[type=hidden]')->attr('value'));
    }

    public function testRendersSkipAnimeForm(): void
    {
        $rendered = $this->renderTwigComponent(
            self::COMPONENT_NAME,
            [
                'user' => new User(),
                'series' => [
                    self::createSeriesResult(
                        animes: [self::createAnimeResult(id: $animeId = 100)],
                    ),
                ],
            ],
        );

        self::assertCount(1, $crawler = $rendered->crawler());
        self::assertCount(1, $section = $crawler->filter('section.series-list'));
        self::assertCount(1, $items = $section->filter('div.series-list-item'));
        self::assertCount(1, $button = $items->selectButton('Skip'));

        $form = $button->ancestors();
        $this->csrfTokenManagerSpy->assertCalls(1);
        self::assertSame("/animes/$animeId/skip", $form->attr('action'));
        self::assertSame(self::CSRF_TOKEN_VALUE, $form->filter('input[type=hidden]')->attr('value'));
    }

    public function testRendersObserveAnimeForm(): void
    {
        $rendered = $this->renderTwigComponent(
            self::COMPONENT_NAME,
            [
                'user' => new User(),
                'series' => [
                    self::createSeriesResult(
                        animes: [self::createAnimeResult(id: $animeId = 6610, state: AnimeRateStatus::SKIPPED)],
                    ),
                ],
            ],
        );

        self::assertCount(1, $crawler = $rendered->crawler());
        self::assertCount(1, $section = $crawler->filter('section.series-list'));
        self::assertCount(1, $items = $section->filter('div.series-list-item'));
        self::assertCount(1, $button = $items->selectButton('Observe'));

        $form = $button->ancestors();
        $this->csrfTokenManagerSpy->assertCalls(1);
        self::assertSame("/animes/$animeId/observe", $form->attr('action'));
        self::assertSame(self::CSRF_TOKEN_VALUE, $form->filter('input[type=hidden]')->attr('value'));
    }

    private static function createSeriesResult(
        string $id = 'series_id',
        string $name = 'series',
        string $seriesRateId = 'series_rate_id',
        SeriesState $state = SeriesState::INCOMPLETE,
        float $score = 0.0,
        array $animes = [],
    ): SeriesResult {
        return new SeriesResult(
            id: $id,
            name: $name,
            seriesRateId: $seriesRateId,
            state: $state,
            score: $score,
            animes: $animes,
        );
    }

    private static function createAnimeResult(
        int $id = 1,
        Kind $kind = null,
        Status $status = Status::ANONS,
        string $name = 'name',
        string $url = '/animes/1',
        AnimeRateStatus $state = null,
        int $score = null,
    ): AnimeResult {
        return new AnimeResult(
            id: $id,
            kind: $kind,
            status: $status,
            name: $name,
            url: $url,
            state: $state,
            score: $score,
        );
    }

    public static function assertTable(Crawler $table, array $expectedHeaders, array $expectedBody): void
    {
        $actualHeaders = $table->filterXPath('//thead/tr')->each(
            fn (Crawler $row): array => $row
                ->filterXPath('//th')->each(
                    fn (Crawler $cell) => $cell->text(),
                ),
        );
        self::assertSame($expectedHeaders, $actualHeaders);

        $actualBody = $table->filterXPath('//tbody/tr')->each(
            fn (Crawler $row): array => $row
                ->filterXPath('//td|//th')->each(
                    function (Crawler $cell) {
                        if (0 === $cell->children()->count()) {
                            return $cell->text();
                        }

                        $texts = $cell
                            ->children()
                            ->filterXPath('*[not(contains(@class, "md:hidden"))]')
                            ->each(fn (Crawler $c) => $c->text())
                        ;

                        return implode(' ', $texts);
                    },
                ),
        );
        self::assertSame($expectedBody, $actualBody);
    }
}
