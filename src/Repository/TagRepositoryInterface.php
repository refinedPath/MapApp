<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use Symfony\Component\Uid\Uuid;

interface TagRepositoryInterface
{
  /** @return Tag[] */
  public function findAllForUser(Uuid $userId): array;

  public function findByIdForUser(Uuid $id, Uuid $userId): ?Tag;

  public function create(Tag $tag): void;
}
