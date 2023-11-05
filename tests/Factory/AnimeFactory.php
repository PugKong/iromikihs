<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Anime;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Anime>
 *
 * @method        Proxy<Anime>   create(array|callable $attributes = [])
 * @method static Proxy<Anime>   createOne(array $attributes = [])
 * @method static Proxy<Anime>   find(object|array|mixed $criteria)
 * @method static Proxy<Anime>   findOrCreate(array $attributes)
 * @method static Proxy<Anime>   first(string $sortedField = 'id')
 * @method static Proxy<Anime>   last(string $sortedField = 'id')
 * @method static Proxy<Anime>   random(array $attributes = [])
 * @method static Proxy<Anime>   randomOrCreate(array $attributes = [])
 * @method static Proxy<Anime>[] all()
 * @method static Proxy<Anime>[] createMany(int $number, array|callable $attributes = [])
 * @method static Proxy<Anime>[] createSequence(iterable|callable $sequence)
 * @method static Proxy<Anime>[] findBy(array $attributes)
 * @method static Proxy<Anime>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Proxy<Anime>[] randomSet(int $number, array $attributes = [])
 */
final class AnimeFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'id' => self::faker()->unique()->numberBetween(),
            'name' => self::faker()->text(),
            'url' => self::faker()->text(),
            'kind' => self::faker()->randomElement(Kind::cases()),
            'status' => self::faker()->randomElement(Status::cases()),
        ];
    }

    protected static function getClass(): string
    {
        return Anime::class;
    }
}
