<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\AnimeRate;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<AnimeRate>
 *
 * @method        Proxy<AnimeRate>   create(array|callable $attributes = [])
 * @method static Proxy<AnimeRate>   createOne(array $attributes = [])
 * @method static Proxy<AnimeRate>   find(object|array|mixed $criteria)
 * @method static Proxy<AnimeRate>   findOrCreate(array $attributes)
 * @method static Proxy<AnimeRate>   first(string $sortedField = 'id')
 * @method static Proxy<AnimeRate>   last(string $sortedField = 'id')
 * @method static Proxy<AnimeRate>   random(array $attributes = [])
 * @method static Proxy<AnimeRate>   randomOrCreate(array $attributes = [])
 * @method static Proxy<AnimeRate>[] all()
 * @method static Proxy<AnimeRate>[] createMany(int $number, array|callable $attributes = [])
 * @method static Proxy<AnimeRate>[] createSequence(iterable|callable $sequence)
 * @method static Proxy<AnimeRate>[] findBy(array $attributes)
 * @method static Proxy<AnimeRate>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Proxy<AnimeRate>[] randomSet(int $number, array $attributes = [])
 */
final class AnimeRateFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'id' => self::faker()->unique()->numberBetween(),
            'user' => UserFactory::new(),
            'anime' => AnimeFactory::new(),
            'score' => self::faker()->randomNumber(),
            'status' => self::faker()->randomElement(UserAnimeStatus::cases()),
        ];
    }

    protected static function getClass(): string
    {
        return AnimeRate::class;
    }
}
