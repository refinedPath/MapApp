<?php

declare(strict_types=1);

namespace App\Http;

use App\Entity\Place;

final class PlaceSerializer
{
  /**
   * @return array<string, mixed>
   */
  public static function toArray(Place $place): array
  {
    return [
      'id' => $place->id->toRfc4122(),
      'name' => $place->name,
      'description' => $place->description,
      'latitude' => $place->location->latitude,
      'longitude' => $place->location->longitude,
      'created_at' => $place->createdAt->format(\DateTimeInterface::ATOM),
      'updated_at' => $place->updatedAt->format(\DateTimeInterface::ATOM),
    ];
  }
}
