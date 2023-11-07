<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Anime;
use App\Entity\AnimeRate;
use App\Entity\User;
use DateTimeImmutable;
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

    public function findNextAnimeToSyncSeriesByUser(User $user): ?Anime
    {
        /** @var AnimeRate|null $result */
        $result = $this
            ->createQueryBuilder('r')
            ->join('r.anime', 'a')
            ->leftJoin('a.series', 's')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->andWhere('s IS NULL OR s.updatedAt < :outdated')
            ->setParameter('outdated', new DateTimeImmutable('-1 month'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $result) {
            return null;
        }

        return $result->getAnime();
    }
}
