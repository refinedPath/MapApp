<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Place;
use App\ReadModel\PlaceView;
use Symfony\Component\Uid\Uuid;

interface PlaceRepositoryInterface
{
  /** @return Place[] */
  public function findAllForUser(Uuid $userId): array;

  public function findByIdForUser(Uuid $id, Uuid $userId): ?Place;

  public function create(Place $place): void;

  /** @return PlaceView[] */
  public function findAllForUserWithPrimaryTag(Uuid $userId): array;

  public function setPrimaryTag(Uuid $placeId, Uuid $tagId): void;

  public function clearPrimaryTag(Uuid $placeId): void;
}
