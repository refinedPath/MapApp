<?php

declare(strict_types=1);

namespace App\Http;

use App\ReadModel\PlaceView;

final class PlaceViewSerializer
{
  /**
   * @return array<string, mixed>
   */
  public static function toArray(PlaceView $place): array
  {
    return [
      'id' => $place->id->toRfc4122(),
      'name' => $place->name,
      'description' => $place->description,
      'latitude' => $place->latitude,
      'longitude' => $place->longitude,
      'primary_tag_id' => $place->primaryTagId?->toRfc4122(),
      'primary_color' => $place->primaryColor,
      'primary_emoji' => $place->primaryEmoji,
      'created_at' => $place->createdAt->format(\DateTimeInterface::ATOM),
      'updated_at' => $place->updatedAt->format(\DateTimeInterface::ATOM),
    ];
  }
}
