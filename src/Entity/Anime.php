<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\AnimeHasNoSeriesException;
use App\Repository\AnimeRepository;
use App\Shikimori\Api\BaseAnimeData;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnimeRepository::class)]
class Anime
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne]
    private ?Series $series = null;

    #[ORM\Column]
    private string $name;

    #[ORM\Column]
    private string $url;

    #[ORM\Column(nullable: true)]
    private ?Kind $kind;

    #[ORM\Column]
    private Status $status;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $airedOn;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $releasedOn;

    public function updateFromBaseData(BaseAnimeData $data): void
    {
        $this->setName($data->name);
        $this->setUrl($data->url);
        $this->setKind($data->kind);
        $this->setStatus($data->status);
        $this->setAiredOn($data->airedOn);
        $this->setReleasedOn($data->releasedOn);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getSeries(): ?Series
    {
        return $this->series;
    }

    public function getSeriesOrFail(): Series
    {
        if (null === $this->series) {
            throw AnimeHasNoSeriesException::create($this);
        }

        return $this->series;
    }

    public function setSeries(?Series $series): self
    {
        $this->series = $series;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getKind(): ?Kind
    {
        return $this->kind;
    }

    public function setKind(?Kind $kind): self
    {
        $this->kind = $kind;

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAiredOn(): ?DateTimeImmutable
    {
        return $this->airedOn;
    }

    public function setAiredOn(?DateTimeImmutable $airedOn): self
    {
        $this->airedOn = $airedOn;

        return $this;
    }

    public function getReleasedOn(): ?DateTimeImmutable
    {
        return $this->releasedOn;
    }

    public function setReleasedOn(?DateTimeImmutable $releasedOn): self
    {
        $this->releasedOn = $releasedOn;

        return $this;
    }
}
