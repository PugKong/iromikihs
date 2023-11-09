<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Series;
use DateTimeImmutable;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Series>
 *
 * @method        Proxy<Series>   create(array|callable $attributes = [])
 * @method static Proxy<Series>   createOne(array $attributes = [])
 * @method static Proxy<Series>   find(object|array|mixed $criteria)
 * @method static Proxy<Series>   findOrCreate(array $attributes)
 * @method static Proxy<Series>   first(string $sortedField = 'id')
 * @method static Proxy<Series>   last(string $sortedField = 'id')
 * @method static Proxy<Series>   random(array $attributes = [])
 * @method static Proxy<Series>   randomOrCreate(array $attributes = [])
 * @method static Proxy<Series>[] all()
 * @method static Proxy<Series>[] createMany(int $number, array|callable $attributes = [])
 * @method static Proxy<Series>[] createSequence(iterable|callable $sequence)
 * @method static Proxy<Series>[] findBy(array $attributes)
 * @method static Proxy<Series>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Proxy<Series>[] randomSet(int $number, array $attributes = [])
 */
final class SeriesFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->text(),
            'updatedAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTimeThisDecade()),
        ];
    }

    protected static function getClass(): string
    {
        return Series::class;
    }
}
