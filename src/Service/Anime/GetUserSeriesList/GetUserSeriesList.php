<?php

declare(strict_types=1);

namespace App\Service\Anime\GetUserSeriesList;

use App\Entity\AnimeRateStatus;
use App\Entity\SeriesState;
use App\Entity\User;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Traversable;

final readonly class GetUserSeriesList
{
    private Connection $connection;

    public function __construct(
        Connection $connection,
    ) {
        $this->connection = $connection;
    }

    /**
     * @return SeriesResult[]
     */
    public function __invoke(User $user, SeriesState $state): array
    {
        $seriesData = $this->findSeries($user, $state);
        $series = [];
        foreach ($seriesData as $row) {
            $series[$row['series_id']] = new SeriesResult(
                id: $row['series_id'],
                name: $row['name'],
                seriesRateId: $row['series_rate_id'],
                state: SeriesState::from($row['state']),
                score: (float) $row['score'],
            );
        }

        $animesData = $this->findAnimes($user, array_keys($series));
        foreach ($animesData as $row) {
            $series[$row['series_id']]->animes[] = new AnimeResult(
                id: $row['id'],
                kind: null !== $row['kind'] ? Kind::from($row['kind']) : null,
                status: Status::from($row['status']),
                name: $row['name'],
                url: $row['url'],
                state: null !== $row['rate_status'] ? AnimeRateStatus::from($row['rate_status']) : null,
                score: $row['score'],
            );
        }

        return array_values($series);
    }

    /**
     * @return Traversable<array{series_id: string, name: string, series_rate_id: string, state: string, score: string}>
     */
    private function findSeries(User $user, SeriesState $state): Traversable
    {
        $sql = <<<'SQL'
            SELECT s.id series_id, name, r.id series_rate_id, state, score
            FROM series s JOIN series_rate r ON s.id = r.series_id
            WHERE r.user_id = :user_id AND r.state = :state
            ORDER BY name
            SQL;

        $result = $this->connection->executeQuery(
            $sql,
            ['user_id' => $user->getId(), 'state' => $state->value],
        );

        // @phpstan-ignore-next-line
        return $result->iterateAssociative();
    }

    /**
     * @param string[] $seriesIds
     *
     * @return Traversable<array{
     *   id: int,
     *   series_id: string,
     *   kind: ?string,
     *   status: string,
     *   name: string,
     *   url: string,
     *   rate_status: ?string,
     *   score: ?int,
     * }>
     */
    private function findAnimes(User $user, array $seriesIds): Traversable
    {
        $sql = <<<'SQL'
            SELECT a.id, a.series_id, a.kind, a.status, a.name, a.url, r.status rate_status, r.score
            FROM anime a LEFT JOIN anime_rate r ON a.id = r.anime_id
            WHERE a.series_id IN (:series_ids) AND (r.user_id = :user_id OR r.user_id IS NULL)
            ORDER BY a.id
            SQL;

        $result = $this->connection->executeQuery(
            $sql,
            ['user_id' => $user->getId(), 'series_ids' => $seriesIds],
            ['series_ids' => ArrayParameterType::STRING],
        );

        // @phpstan-ignore-next-line
        return $result->iterateAssociative();
    }
}
