<?php

declare(strict_types=1);

namespace App\Repository;

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
}
