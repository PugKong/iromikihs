<?php

declare(strict_types=1);

namespace App\Service\Series;

use App\Shikimori\Api\BaseAnimeData;
use Symfony\Component\String\UnicodeString;

use function array_key_exists;
use function count;
use function in_array;
use function strlen;

/**
 * This class has GPT suffix to emphasize its results quality.
 * Sometimes they are OK, but sometimes they are just like a hallucination.
 */
final readonly class NameGpt
{
    private const NAME_DELIMITERS = [' ', '/', ':'];

    /**
     * @param BaseAnimeData[] $animes
     */
    public function __invoke(array $animes): string
    {
        if (0 === count($animes)) {
            return '';
        }

        $longestPrefix = $this->longestPrefix($animes);
        if (null !== $longestPrefix) {
            return $longestPrefix;
        }

        return $this->firstByAiredDate($animes);
    }

    /**
     * @param BaseAnimeData[] $animes
     */
    private function longestPrefix(array $animes): ?string
    {
        /** @var array<string|int, int> $prefixes prefix to occurrence map */
        // php will convert key string(91) to int(91), so key is string or int
        $prefixes = [];
        foreach ($animes as $anime) {
            $name = new UnicodeString($anime->name);
            for ($i = 1; $i <= $name->length(); ++$i) {
                if (in_array($name->slice($i - 1, 1), self::NAME_DELIMITERS)) {
                    continue;
                }

                if ($i !== $name->length() && !in_array($name->slice($i, 1), self::NAME_DELIMITERS)) {
                    continue;
                }
                $prefix = $name->slice(length: $i)->toString();
                if (!array_key_exists($prefix, $prefixes)) {
                    $prefixes[$prefix] = 0;
                }
                ++$prefixes[$prefix];
            }
        }

        $maxOccurrence = 0;
        $longestPrefix = new UnicodeString('');
        foreach ($prefixes as $prefix => $count) {
            if ($count < $maxOccurrence) {
                continue;
            }

            $prefix = new UnicodeString((string) $prefix);
            if ($longestPrefix->length() < $prefix->length()) {
                $maxOccurrence = $count;
                $longestPrefix = $prefix;
            }
        }

        $minOccurrence = count($animes);
        $longestPrefix = $longestPrefix->trim(' /');
        if ($maxOccurrence < $minOccurrence || $longestPrefix->length() < 4) {
            return null;
        }

        if ($longestPrefix->lower()->endsWith($suffix = ' movie')) {
            return $longestPrefix->slice(0, -strlen($suffix))->toString();
        }

        return $longestPrefix->toString();
    }

    /**
     * @param BaseAnimeData[] $animes
     */
    private function firstByAiredDate(array $animes): string
    {
        usort($animes, function (BaseAnimeData $a, BaseAnimeData $b): int {
            if (null !== $a->airedOn && null !== $b->airedOn) {
                return $a->airedOn <=> $b->airedOn;
            }

            if (null === $a->airedOn && null === $b->airedOn) {
                return 0;
            }

            if (null === $a->airedOn) {
                return 1;
            }

            return -1;
        });

        return $animes[0]->name;
    }
}
