<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\SeriesState;
use App\Entity\UserSeriesState;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<SeriesState>
 *
 * @method        Proxy<SeriesState>   create(array|callable $attributes = [])
 * @method static Proxy<SeriesState>   createOne(array $attributes = [])
 * @method static Proxy<SeriesState>   find(object|array|mixed $criteria)
 * @method static Proxy<SeriesState>   findOrCreate(array $attributes)
 * @method static Proxy<SeriesState>   first(string $sortedField = 'id')
 * @method static Proxy<SeriesState>   last(string $sortedField = 'id')
 * @method static Proxy<SeriesState>   random(array $attributes = [])
 * @method static Proxy<SeriesState>   randomOrCreate(array $attributes = [])
 * @method static Proxy<SeriesState>[] all()
 * @method static Proxy<SeriesState>[] createMany(int $number, array|callable $attributes = [])
 * @method static Proxy<SeriesState>[] createSequence(iterable|callable $sequence)
 * @method static Proxy<SeriesState>[] findBy(array $attributes)
 * @method static Proxy<SeriesState>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Proxy<SeriesState>[] randomSet(int $number, array $attributes = [])
 */
final class SeriesStateFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'user' => LazyValue::new(fn () => UserFactory::new()),
            'series' => LazyValue::new(fn () => SeriesFactory::new()),
            'state' => self::faker()->randomElement(UserSeriesState::cases()),
        ];
    }

    protected static function getClass(): string
    {
        return SeriesState::class;
    }
}
