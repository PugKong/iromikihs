<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AnimeRate;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnimeRate>
 */
class AnimeRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnimeRate::class);
    }

    /**
     * @return AnimeRate[]
     */
    public function findByUserWithAnime(User $user): array
    {
        /** @var AnimeRate[] $result */
        $result = $this
            ->createQueryBuilder('r')
            ->addSelect('a')
            ->join('r.anime', 'a')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * @param int[] $ids
     *
     * @return AnimeRate[]
     */
    public function findByUserOrIdsIndexedById(User $user, array $ids): array
    {
        /** @var AnimeRate[] $result */
        $result = $this
            ->createQueryBuilder('r', 'r.id')
            ->orWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orWhere('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }
}
