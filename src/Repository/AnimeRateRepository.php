<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Anime;
use App\Entity\AnimeRate;
use App\Entity\AnimeRateStatus;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\UuidV7;

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
     * @return UserRatedAnime[]
     */
    public function findUserRatedAnime(User $user): array
    {
        /** @var UserRatedAnime[] $result */
        $result = $this
            ->createQueryBuilder('r')
            ->select(sprintf(
                'NEW %s(a.name, a.kind, a.status, a.url, r.status, r.score)',
                UserRatedAnime::class,
            ))
            ->join('r.anime', 'a')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.name')
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
    public function findByUserOrShikimoriIdsIndexedByShikimoriId(User $user, array $ids): array
    {
        /** @var AnimeRate[] $result */
        $result = $this
            ->createQueryBuilder('r', 'r.shikimoriId')
            ->orWhere((new Expr())->andX('r.user = :user', 'r.shikimoriId IS NOT NULL'))
            ->setParameter('user', $user)
            ->orWhere('r.shikimoriId IN (:ids)')
            ->andWhere('r.status != :skipped')
            ->setParameter('skipped', AnimeRateStatus::SKIPPED)
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

    /**
     * @return UuidV7[]
     */
    public function findSeriesIdsByUser(User $user): array
    {
        /** @var list<array{id: UuidV7}> $result */
        $result = $this
            ->createQueryBuilder('r')
            ->select('DISTINCT s.id')
            ->join('r.anime', 'a')
            ->join('a.series', 's')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;

        return array_column($result, 'id');
    }
}
