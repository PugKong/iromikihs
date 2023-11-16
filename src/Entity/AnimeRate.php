<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AnimeRateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: AnimeRateRepository::class)]
#[ORM\UniqueConstraint(columns: ['user_id', 'shikimori_id'])]
class AnimeRate
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private UuidV7 $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(nullable: true)]
    private ?int $shikimoriId;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Anime $anime;

    #[ORM\Column]
    private int $score;

    #[ORM\Column]
    private AnimeRateStatus $status;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): UuidV7
    {
        return $this->id;
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

    public function getShikimoriId(): ?int
    {
        return $this->shikimoriId;
    }

    public function setShikimoriId(?int $shikimoriId): self
    {
        $this->shikimoriId = $shikimoriId;

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

    public function getStatus(): AnimeRateStatus
    {
        return $this->status;
    }

    public function setStatus(AnimeRateStatus $status): self
    {
        $this->status = $status;
        if (AnimeRateStatus::SKIPPED === $status) {
            $this->setShikimoriId(null);
            $this->setScore(0);
        }

        return $this;
    }
}
