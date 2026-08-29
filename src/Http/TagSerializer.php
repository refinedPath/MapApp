<?php

declare(strict_types=1);

namespace App\Http;

use App\Entity\Tag;

final class TagSerializer
{
  /**
   * @return array<string, mixed>
   */
  public static function toArray(Tag $tag): array
  {
    return [
      'id' => $tag->id->toRfc4122(),
      'name' => $tag->name,
      'color' => $tag->color,
      'emoji' => $tag->emoji,
      'created_at' => $tag->createdAt->format(\DateTimeInterface::ATOM),
      'updated_at' => $tag->updatedAt->format(\DateTimeInterface::ATOM),
    ];
  }
}
