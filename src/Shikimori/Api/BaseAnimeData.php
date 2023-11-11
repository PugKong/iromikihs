<?php

declare(strict_types=1);

namespace App\Shikimori\Api;

use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

readonly class BaseAnimeData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $url,
        public ?Kind $kind,
        public Status $status,
        #[SerializedName('aired_on')]
        #[Context([DateTimeNormalizer::FORMAT_KEY => '!Y-m-d'])]
        public ?DateTimeImmutable $airedOn,
        #[SerializedName('released_on')]
        #[Context([DateTimeNormalizer::FORMAT_KEY => '!Y-m-d'])]
        public ?DateTimeImmutable $releasedOn,
    ) {
    }
}
