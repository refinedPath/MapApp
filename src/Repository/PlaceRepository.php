<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Coordinates;
use App\Entity\Place;
use App\ReadModel\PlaceView;
use Override;
use PDO;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-type PlaceViewRow array{
 *   id: string,
 *   name: string,
 *   description: string|null,
 *   latitude: string,
 *   longitude: string,
 *   primary_tag_id: string|null,
 *   primary_color: string|null,
 *   primary_emoji: string|null,
 *   created_at: string,
 *   updated_at: string
 * }
 */
final class PlaceRepository implements PlaceRepositoryInterface
{
  public function __construct(
    private readonly PDO $pdo,
  ) {
  }

  /** @return Place[] */
  #[Override]
  public function findAllForUser(Uuid $userId): array
  {
    $stmt = $this->pdo->prepare(
      'SELECT id, user_id, name, description,
              ST_Y(location::geometry) AS latitude,
              ST_X(location::geometry) AS longitude,
              created_at, updated_at
      FROM places
      WHERE user_id = :user_id
      ORDER BY created_at DESC'
    );
    $stmt->execute([
      'user_id' => $userId->toRfc4122(),
    ]);

    /** @var list<array<string, string>> $rows */
    $rows = $stmt->fetchAll();

    return array_map(
      fn (array $row): Place => $this->hydrate($row),
      $rows
    );
  }

  #[Override]
  public function findByIdForUser(Uuid $id, Uuid $userId): ?Place
  {
    $stmt = $this->pdo->prepare(
      'SELECT id, user_id, name, description,
              ST_Y(location::geometry) AS latitude,
              ST_X(location::geometry) AS longitude,
              created_at, updated_at
      FROM places
      WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute([
      'id' => $id->toRfc4122(),
      'user_id' => $userId->toRfc4122(),
    ]);

    /** @var array<string, string>|false $row */
    $row = $stmt->fetch();

    return $row === false ? null : $this->hydrate($row);
  }

  #[Override]
  public function create(Place $place): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO places (id, user_id, name, description, location, created_at, updated_at)
      VALUES (:id, :user_id, :name, :description,
              ST_SetSRID(ST_MakePoint(:lng, :lat), 4326)::geography,
              :created_at, :updated_at)'
    );
    $stmt->execute([
      'id' => $place->id->toRfc4122(),
      'user_id' => $place->userId->toRfc4122(),
      'name' => $place->name,
      'description' => $place->description,
      'lng' => $place->location->longitude,
      'lat' => $place->location->latitude,
      'created_at' => $place->createdAt->format('Y-m-d H:i:sP'),
      'updated_at' => $place->updatedAt->format('Y-m-d H:i:sP'),
    ]);
  }

  #[Override]
  public function update(
    Uuid $id,
    Uuid $userId,
    string $name,
    ?string $description,
    \DateTimeImmutable $updatedAt,
  ): int {
    $stmt = $this->pdo->prepare(
      'UPDATE places
      SET name = :name, description = :description, updated_at = :updated_at
      WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute([
      'id' => $id->toRfc4122(),
      'user_id' => $userId->toRfc4122(),
      'name' => $name,
      'description' => $description,
      'updated_at' => $updatedAt->format('Y-m-d H:i:sP'),
    ]);

    return $stmt->rowCount();
  }

  /**
   * @param array<string, string> $row
   */
  private function hydrate(array $row): Place
  {
    return new Place(
      id: Uuid::fromString($row['id']),
      userId: Uuid::fromString($row['user_id']),
      name: $row['name'],
      description: $row['description'],
      location: new Coordinates(
        latitude: (float) $row['latitude'],
        longitude: (float) $row['longitude'],
      ),
      createdAt: new \DateTimeImmutable($row['created_at']),
      updatedAt: new \DateTimeImmutable($row['updated_at']),
    );
  }

  /** @return PlaceView[] */
  #[Override]
  public function findAllForUserWithPrimaryTag(Uuid $userId): array
  {
    $stmt = $this->pdo->prepare(
      'SELECT p.id, p.name, p.description,
                ST_Y(p.location::geometry) AS latitude,
                ST_X(p.location::geometry) AS longitude,
                p.primary_tag_id,
                t.color AS primary_color,
                t.emoji AS primary_emoji,
                p.created_at, p.updated_at
        FROM places p
        LEFT JOIN tags t ON t.id = p.primary_tag_id
        WHERE p.user_id = :user_id
        ORDER BY p.created_at DESC'
    );
    $stmt->execute(['user_id' => $userId->toRfc4122()]);

    /** @var list<PlaceViewRow> $rows */
    $rows = $stmt->fetchAll();

    return array_map(fn (array $row): PlaceView => $this->hydratePlaceView($row), $rows);
  }

  #[Override]
  public function findByIdForUserWithPrimaryTag(Uuid $id, Uuid $userId): ?PlaceView
  {
    $stmt = $this->pdo->prepare(
      'SELECT p.id, p.name, p.description,
                ST_Y(p.location::geometry) AS latitude,
                ST_X(p.location::geometry) AS longitude,
                p.primary_tag_id,
                t.color AS primary_color,
                t.emoji AS primary_emoji,
                p.created_at, p.updated_at
        FROM places p
        LEFT JOIN tags t ON t.id = p.primary_tag_id
        WHERE p.id = :id AND p.user_id = :user_id'
    );
    $stmt->execute([
      'id' => $id->toRfc4122(),
      'user_id' => $userId->toRfc4122(),
    ]);

    /** @var PlaceViewRow|false $row */
    $row = $stmt->fetch();

    return $row === false ? null : $this->hydratePlaceView($row);
  }

  /** @param PlaceViewRow $row */
  private function hydratePlaceView(array $row): PlaceView
  {
    return new PlaceView(
      id: Uuid::fromString($row['id']),
      name: $row['name'],
      description: $row['description'],
      latitude: (float) $row['latitude'],
      longitude: (float) $row['longitude'],
      primaryTagId: $row['primary_tag_id'] === null ? null : Uuid::fromString($row['primary_tag_id']),
      primaryColor: $row['primary_color'],
      primaryEmoji: $row['primary_emoji'],
      createdAt: new \DateTimeImmutable($row['created_at']),
      updatedAt: new \DateTimeImmutable($row['updated_at']),
    );
  }

  #[Override]
  public function setPrimaryTag(Uuid $placeId, Uuid $tagId): void
  {
    $stmt = $this->pdo->prepare(
      'UPDATE places SET primary_tag_id = :tag_id WHERE id = :place_id'
    );
    $stmt->execute([
      'place_id' => $placeId->toRfc4122(),
      'tag_id' => $tagId->toRfc4122(),
    ]);
  }

  #[Override]
  public function clearPrimaryTag(Uuid $placeId): void
  {
    $stmt = $this->pdo->prepare(
      'UPDATE places SET primary_tag_id = NULL WHERE id = :place_id'
    );
    $stmt->execute(['place_id' => $placeId->toRfc4122()]);
  }

  #[Override]
  public function clearPrimaryTagIfMatches(Uuid $placeId, Uuid $tagId): void
  {
    $stmt = $this->pdo->prepare(
      'UPDATE places SET primary_tag_id = NULL WHERE id = :place_id AND primary_tag_id = :tag_id'
    );
    $stmt->execute([
      'place_id' => $placeId->toRfc4122(),
      'tag_id' => $tagId->toRfc4122(),
    ]);
  }
}
