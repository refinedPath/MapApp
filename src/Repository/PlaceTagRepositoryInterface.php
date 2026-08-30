<?php

declare(strict_types=1);

namespace App\Repository;

use Symfony\Component\Uid\Uuid;

interface PlaceTagRepositoryInterface
{
  public function assign(Uuid $placeId, Uuid $tagId): void;
  public function unassign(Uuid $placeId, Uuid $tagId): void;
}
