<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SeriesRateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: SeriesRateRepository::class)]
#[ORM\UniqueConstraint(columns: ['user_id', 'series_id'])]
class SeriesRate
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private UuidV7 $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Series $series;

    #[ORM\Column]
    private float $score;

    #[ORM\Column]
    private SeriesState $state;

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

    public function getSeries(): Series
    {
        return $this->series;
    }

    public function setSeries(Series $series): self
    {
        $this->series = $series;

        return $this;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getState(): SeriesState
    {
        return $this->state;
    }

    public function setState(SeriesState $state): self
    {
        $this->state = $state;

        return $this;
    }
}
