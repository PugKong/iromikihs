<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\SeriesRate;
use App\Entity\SeriesState;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<SeriesRate>
 *
 * @method        Proxy<SeriesRate>   create(array|callable $attributes = [])
 * @method static Proxy<SeriesRate>   createOne(array $attributes = [])
 * @method static Proxy<SeriesRate>   find(object|array|mixed $criteria)
 * @method static Proxy<SeriesRate>   findOrCreate(array $attributes)
 * @method static Proxy<SeriesRate>   first(string $sortedField = 'id')
 * @method static Proxy<SeriesRate>   last(string $sortedField = 'id')
 * @method static Proxy<SeriesRate>   random(array $attributes = [])
 * @method static Proxy<SeriesRate>   randomOrCreate(array $attributes = [])
 * @method static Proxy<SeriesRate>[] all()
 * @method static Proxy<SeriesRate>[] createMany(int $number, array|callable $attributes = [])
 * @method static Proxy<SeriesRate>[] createSequence(iterable|callable $sequence)
 * @method static Proxy<SeriesRate>[] findBy(array $attributes)
 * @method static Proxy<SeriesRate>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Proxy<SeriesRate>[] randomSet(int $number, array $attributes = [])
 */
final class SeriesRateFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'user' => LazyValue::new(fn () => UserFactory::new()),
            'series' => LazyValue::new(fn () => SeriesFactory::new()),
            'score' => self::faker()->randomFloat(2, 0, 10),
            'state' => self::faker()->randomElement(SeriesState::cases()),
        ];
    }

    protected static function getClass(): string
    {
        return SeriesRate::class;
    }
}
