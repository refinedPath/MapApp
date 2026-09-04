<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Place;
use App\Enum\TagMatchMode;
use App\ReadModel\PlaceView;
use Symfony\Component\Uid\Uuid;

interface PlaceRepositoryInterface
{
  /** @return Place[] */
  public function findAllForUser(Uuid $userId): array;

  public function findByIdForUser(Uuid $id, Uuid $userId): ?Place;

  /** @return PlaceView[] */
  public function findAllForUserWithPrimaryTag(Uuid $userId): array;

  public function findByIdForUserWithPrimaryTag(Uuid $id, Uuid $userId): ?PlaceView;

  /**
   * @param list<Uuid> $tagIds
   * @return PlaceView[]
   */
  public function findAllForUserMatchingTags(
    Uuid $userId,
    array $tagIds,
    TagMatchMode $mode,
  ): array;

  public function create(Place $place): void;

  public function delete(Uuid $id, Uuid $userId): int;

  public function setPrimaryTag(Uuid $placeId, Uuid $tagId): void;

  public function clearPrimaryTag(Uuid $placeId): void;

  public function clearPrimaryTagIfMatches(Uuid $placeId, Uuid $tagId): void;

  public function update(
    Uuid $id,
    Uuid $userId,
    string $name,
    ?string $description,
    \DateTimeImmutable $updatedAt,
  ): int;
}
