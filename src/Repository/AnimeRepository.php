<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Anime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Anime>
 */
class AnimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anime::class);
    }

    public function findOrNew(int $id): Anime
    {
        $anime = $this->find($id);
        if (null === $anime) {
            $anime = new Anime();
            $anime->setId($id);
        }

        return $anime;
    }
}
