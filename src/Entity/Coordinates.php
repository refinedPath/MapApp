<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\InvalidCoordinatesException;

final readonly class Coordinates
{
  public function __construct(
    public float $latitude,
    public float $longitude,
  ) {
    if ($latitude < -90.0 || $latitude > 90.0) {
      throw new InvalidCoordinatesException("Latitude out of range: {$latitude}");
    }
    if ($longitude < -180.0 || $longitude > 180.0) {
      throw new InvalidCoordinatesException("Longitude out of range: {$longitude}");
    }
  }
}
