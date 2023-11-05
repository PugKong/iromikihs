<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AnimeRateRepository;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnimeRateRepository::class)]
class AnimeRate
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Anime $anime;

    #[ORM\Column]
    private int $score;

    #[ORM\Column]
    private UserAnimeStatus $status;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAnime(): Anime
    {
        return $this->anime;
    }

    public function setAnime(Anime $anime): self
    {
        $this->anime = $anime;

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getStatus(): UserAnimeStatus
    {
        return $this->status;
    }

    public function setStatus(UserAnimeStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
}
