<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use Override;
use PDO;
use Symfony\Component\Uid\Uuid;

final class PlaceTagRepository implements PlaceTagRepositoryInterface
{
  public function __construct(
    private readonly PDO $pdo,
  ) {
  }

  #[Override]
  public function assign(Uuid $placeId, Uuid $tagId): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO place_tags (place_id, tag_id)
      VALUES (:place_id, :tag_id)
      ON CONFLICT (place_id, tag_id) DO NOTHING'
    );
    $stmt->execute([
      'place_id' => $placeId->toRfc4122(),
      'tag_id' => $tagId->toRfc4122(),
    ]);
  }

  #[Override]
  public function unassign(Uuid $placeId, Uuid $tagId): void
  {
    $stmt = $this->pdo->prepare(
      'DELETE FROM place_tags
      WHERE place_id = :place_id AND tag_id = :tag_id'
    );
    $stmt->execute([
      'place_id' => $placeId->toRfc4122(),
      'tag_id' => $tagId->toRfc4122(),
    ]);
  }

  #[Override]
  public function isAssigned(Uuid $placeId, Uuid $tagId): bool
  {
    $stmt = $this->pdo->prepare(
      'SELECT 1 FROM place_tags
        WHERE place_id = :place_id AND tag_id = :tag_id
        LIMIT 1'
    );
    $stmt->execute([
      'place_id' => $placeId->toRfc4122(),
      'tag_id' => $tagId->toRfc4122(),
    ]);

    return $stmt->fetchColumn() !== false;
  }

  #[Override]
  public function findTagsForPlace(Uuid $placeId): array
  {
    $stmt = $this->pdo->prepare(
      'SELECT t.id, t.user_id, t.name, t.color, t.emoji, t.created_at, t.updated_at
        FROM place_tags pt
        JOIN tags t ON t.id = pt.tag_id
        WHERE pt.place_id = :place_id
        ORDER BY t.name ASC'
    );
    $stmt->execute([
      'place_id' => $placeId->toRfc4122(),
    ]);

    /** @var list<array<string, string>> $rows */
    $rows = $stmt->fetchAll();

    return array_map(
      fn (array $row): Tag => TagHydrator::fromRow($row),
      $rows
    );
  }
}
