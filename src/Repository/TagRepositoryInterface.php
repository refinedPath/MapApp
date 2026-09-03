<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use App\ReadModel\TagView;
use Symfony\Component\Uid\Uuid;

interface TagRepositoryInterface
{
  /** @return Tag[] */
  public function findAllForUser(Uuid $userId): array;

  public function findByIdForUser(Uuid $id, Uuid $userId): ?Tag;

  public function create(Tag $tag): void;

  public function update(
    Uuid $id,
    Uuid $userId,
    string $name,
    string $color,
    ?string $emoji,
    \DateTimeImmutable $updatedAt,
  ): int;

  public function delete(Uuid $id, Uuid $userId): int;

  /** @return TagView[] */
  public function findAllForUserWithCounts(Uuid $userId): array;
}
