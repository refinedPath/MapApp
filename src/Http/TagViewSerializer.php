<?php

declare(strict_types=1);

namespace App\Http;

use App\ReadModel\TagView;

final class TagViewSerializer
{
  /**
   * @return array<string, mixed>
   */
  public static function toArray(TagView $tag): array
  {
    return [
      'id' => $tag->id->toRfc4122(),
      'name' => $tag->name,
      'color' => $tag->color,
      'emoji' => $tag->emoji,
      'assignment_count' => $tag->assignmentCount,
    ];
  }
}
