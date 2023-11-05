<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AnimeRepository;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnimeRepository::class)]
class Anime
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private string $name;

    #[ORM\Column]
    private string $url;

    #[ORM\Column]
    private Kind $kind;

    #[ORM\Column]
    private Status $status;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

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

    public function getKind(): Kind
    {
        return $this->kind;
    }

    public function setKind(Kind $kind): self
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
}
