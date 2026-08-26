<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Coordinates;
use App\Entity\Place;
use PDO;
use Symfony\Component\Uid\Uuid;

final class PlaceRepository implements PlaceRepositoryInterface
{
  public function __construct(
    private readonly PDO $pdo,
  ) {}

  /** @return Place[] */
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
      fn(array $row): Place => $this->hydrate($row),
      $rows
    );
  }

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
}
