<?php

declare(strict_types=1);

namespace App\Tests\Service\Series;

use App\Service\Series\NameGpt;
use App\Shikimori\Api\BaseAnimeData;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Tests\Service\ServiceTestCase;
use App\Tests\TestDouble\Shikimori\BaseAnimeDataStub;
use DateTimeImmutable;

final class NameGptTest extends ServiceTestCase
{
    /**
     * @dataProvider calculationDataProvider
     *
     * @param BaseAnimeData[] $animes
     */
    public function testCalculation(string $expected, array $animes): void
    {
        $calculator = self::getService(NameGpt::class);
        $actual = ($calculator)($animes);
        self::assertSame($expected, $actual);
    }

    /**
     * @group slow
     *
     * @dataProvider calculationDataProvider
     *
     * @param BaseAnimeData[] $animes
     */
    public function testCalculationWithShuffle(string $expected, array $animes): void
    {
        $calculator = self::getService(NameGpt::class);

        for ($i = 0; $i < 100; ++$i) {
            shuffle($animes);
            $actual = ($calculator)($animes);
            self::assertSame($expected, $actual);
        }
    }

    private static function createData(int $id, string $name, ?string $date): BaseAnimeDataStub
    {
        return new BaseAnimeDataStub(
            id: $id,
            name: $name,
            url: "/animes/$id",
            kind: Kind::TV,
            status: Status::RELEASED,
            airedOn: null !== $date ? new DateTimeImmutable($date) : null,
            releasedOn: null,
        );
    }

    public static function calculationDataProvider(): array
    {
        return [
            'empty array' => ['', []],
            '.hack series' => [
                '.hack',
                [
                    self::createData(48, '.hack//Sign', '2002-04-04'),
                    self::createData(299, '.hack//Liminality', '2002-06-20'),
                    self::createData(298, '.hack//Tasogare no Udewa Densetsu', '2003-01-09'),
                ],
            ],
            'Ansatsu Kyoushitsu' => [
                'Ansatsu Kyoushitsu',
                [
                    self::createData(28405, 'Ansatsu Kyoushitsu: Deai no Jikan', '2014-11-09'),
                    self::createData(24833, 'Ansatsu Kyoushitsu', '2015-01-10'),
                    self::createData(30654, 'Ansatsu Kyoushitsu 2nd Season', '2016-01-08'),
                ],
            ],
            'Appleseed' => [
                'Appleseed',
                [
                    self::createData(54, 'Appleseed (Movie)', '2004-04-17'),
                    self::createData(2969, 'Appleseed Saga Ex Machina', '2007-10-20'),
                ],
            ],
            'Mononoke' => [
                'Ayakashi: Japanese Classic Horror',
                [
                    self::createData(586, 'Ayakashi: Japanese Classic Horror', '2006-01-13'),
                    self::createData(2246, 'Mononoke', '2007-07-13'),
                ],
            ],
            'Made in Abyss' => [
                'Made in Abyss',
                [
                    self::createData(34599, 'Made in Abyss', '2017-07-07'),
                    self::createData(37514, 'Made in Abyss Movie 1: Tabidachi no Yoake', '2019-01-04'),
                    self::createData(37515, 'Made in Abyss Movie 2: Hourou Suru Tasogare', '2019-01-18'),
                    self::createData(36862, 'Made in Abyss Movie 3: Fukaki Tamashii no Reimei', '2020-01-17'),
                    self::createData(41084, 'Made in Abyss: Retsujitsu no Ougonkyou', '2022-07-06'),
                    self::createData(54250, 'Made in Abyss: Retsujitsu no Ougonkyou (Zoku-hen)', null),
                ],
            ],
            'Bakemonogatari fiesta' => [
                'Bakemonogatari',
                [
                    self::createData(5081, 'Bakemonogatari', '2009-07-03'),
                    self::createData(11597, 'Nisemonogatari', '2012-01-08'),
                    self::createData(15689, 'Nekomonogatari: Kuro', '2012-12-31'),
                    self::createData(17074, 'Monogatari Series: Second Season', '2013-07-07'),
                    self::createData(28025, 'Tsukimonogatari', '2014-12-31'),
                    self::createData(31181, 'Owarimonogatari', '2015-10-04'),
                    self::createData(9260, 'Kizumonogatari I: Tekketsu-hen', '2016-01-08'),
                    self::createData(32268, 'Koyomimonogatari', '2016-01-10'),
                    self::createData(31757, 'Kizumonogatari II: Nekketsu-hen', '2016-08-19'),
                    self::createData(31758, 'Kizumonogatari III: Reiketsu-hen', '2017-01-06'),
                    self::createData(35247, 'Kizumonogatari III: Reiketsu-hen', '2017-08-12'),
                    self::createData(36999, 'Zoku Owarimonogatari', '2019-05-19'),
                ],
            ],
            'Kara no Kyoukai' => [
                'Kara no Kyoukai',
                [
                    self::createData(2593, 'Kara no Kyoukai Movie 1: Fukan Fuukei', '2007-12-01'),
                    self::createData(3782, 'Kara no Kyoukai Movie 2: Satsujin Kousatsu (Zen)', '2007-12-29'),
                    self::createData(3783, 'Kara no Kyoukai Movie 3: Tsuukaku Zanryuu', '2008-02-09'),
                    self::createData(4280, 'Kara no Kyoukai Movie 4: Garan no Dou', '2008-05-24'),
                ],
            ],
            'ef: A Tale of Memories.' => [
                'ef: A Tale of',
                [
                    self::createData(6361, 'ef: A Tale of Memories. - Prologue', '2007-08-24'),
                    self::createData(2924, 'ef: A Tale of Memories.', '2007-10-07'),
                    self::createData(6401, 'ef: A Tale of Melodies. - Prologue', '2008-05-10'),
                    self::createData(4789, 'ef: A Tale of Melodies.', '2008-10-07'),
                ],
            ],
            '91 Days' => [
                '91 Days',
                [
                    self::createData(32998, '91 Days', '2016-07-09'),
                    self::createData(34777, '91 Days: Toki no Asase/Subete no Kinou/Ashita, Mata Ashita', '2017-07-05'),
                ],
            ],
            'Darker than Black' => [
                'Darker than Black',
                [
                    self::createData(2025, 'Darker than Black: Kuro no Keiyakusha', '2007-04-06'),
                    self::createData(6573, 'Darker than Black: Ryuusei no Gemini', '2009-10-09'),
                    self::createData(7338, 'Darker than Black: Kuro no Keiyakusha Gaiden', '2010-01-27'),
                ],
            ],
            'Tsubasa' => [
                'Tsubasa',
                [
                    self::createData(177, 'Tsubasa Chronicle', '2005-04-09'),
                    self::createData(969, 'Tsubasa Chronicle 2nd Season', '2006-04-29'),
                    self::createData(2685, 'Tsubasa: Tokyo Revelations', '2007-11-16'),
                    self::createData(4938, 'Tsubasa: Shunraiki', '2009-03-17'),
                ],
            ],
            'Durarara fiesta' => [
                'Durarara!!',
                [
                    self::createData(6746, 'Durarara!!', '2010-01-08'),
                    self::createData(23199, 'Durarara!!x2 Shou', '2015-01-10'),
                    self::createData(27831, 'Durarara!!x2 Ten', '2015-07-04'),
                    self::createData(27833, 'Durarara!!x2 Ketsu', '2016-01-09'),
                ],
            ],
        ];
    }
}
