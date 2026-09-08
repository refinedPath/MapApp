<?php

declare(strict_types=1);

namespace App\Validation;

use App\Entity\Coordinates;
use App\Exception\InvalidCoordinatesException;

final class CoordinateInput
{
  /**
   * Parse and validate a latitude/longitude pair from request input.
   *
   * On success: [Coordinates, []]. On failure: [null, field-keyed errors].
   * Numeric checks run first (keyed 'latitude'/'longitude'). A numeric pair
   * that is out of range is rejected by the Coordinates value object and
   * keyed 'location'.
   *
   * @return array{0: ?Coordinates, 1: array<string, string>}
   */
  public static function parse(mixed $latitude, mixed $longitude): array
  {
    $errors = [];
    if (!is_numeric($latitude)) {
      $errors['latitude'] = 'Latitude is required and must be numeric.';
    }
    if (!is_numeric($longitude)) {
      $errors['longitude'] = 'Longitude is required and must be numeric.';
    }
    if ($errors !== []) {
      return [null, $errors];
    }

    try {
      $coordinates = new Coordinates(
        latitude: (float) $latitude,
        longitude: (float) $longitude,
      );
    } catch (InvalidCoordinatesException $e) {
      return [null, ['location' => $e->getMessage()]];
    }

    return [$coordinates, []];
  }
}
