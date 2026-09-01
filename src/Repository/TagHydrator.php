<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use Symfony\Component\Uid\Uuid;

final class TagHydrator
{
  /** @param array<string, string> $row */
  public static function fromRow(array $row): Tag
  {
    return new Tag(
      id: Uuid::fromString($row['id']),
      userId: Uuid::fromString($row['user_id']),
      name: $row['name'],
      color: $row['color'],
      emoji: $row['emoji'],
      createdAt: new \DateTimeImmutable($row['created_at']),
      updatedAt: new \DateTimeImmutable($row['updated_at']),
    );
  }
}
