<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Coordinates;
use App\Validation\CoordinateInput;
use PHPUnit\Framework\TestCase;

final class CoordinateInputTest extends TestCase
{
  public function testParsesValidCoordinates(): void
  {
    [$coords, $errors] = CoordinateInput::parse(40.5, -74.25);

    self::assertInstanceOf(Coordinates::class, $coords);
    self::assertSame(40.5, $coords->latitude);
    self::assertSame(-74.25, $coords->longitude);
    self::assertSame([], $errors);
  }

  public function testAcceptsNumericStrings(): void
  {
    // Request input often arrives as strings. is_numeric accepts them.
    [$coords, $errors] = CoordinateInput::parse('40.5', '-74.25');

    self::assertInstanceOf(Coordinates::class, $coords);
    self::assertSame([], $errors);
  }

  public function testRejectsNonNumericLatitude(): void
  {
    [$coords, $errors] = CoordinateInput::parse('not-a-number', 0);

    self::assertNull($coords);
    self::assertArrayHasKey('latitude', $errors);
    self::assertArrayNotHasKey('location', $errors);
  }

  public function testRejectsNonNumericLongitude(): void
  {
    [$coords, $errors] = CoordinateInput::parse(0, 'not-a-number');

    self::assertNull($coords);
    self::assertArrayHasKey('longitude', $errors);
  }

  public function testRejectsMissingCoordinatesAsNonNumeric(): void
  {
    // Absent fields arrive as null. Treated as required-and-numeric failures.
    [$coords, $errors] = CoordinateInput::parse(null, null);

    self::assertNull($coords);
    self::assertArrayHasKey('latitude', $errors);
    self::assertArrayHasKey('longitude', $errors);
  }

  public function testRejectsOutOfRangeLatitudeAsLocation(): void
  {
    // Numeric but beyond +/-90 passes the numeric guard, rejected by the VO,
    // so it's keyed 'location', not 'latitude'.
    [$coords, $errors] = CoordinateInput::parse(91, 0);

    self::assertNull($coords);
    self::assertArrayHasKey('location', $errors);
    self::assertArrayNotHasKey('latitude', $errors);
  }

  public function testRejectsOutOfRangeLongitudeAsLocation(): void
  {
    [$coords, $errors] = CoordinateInput::parse(0, 181);

    self::assertNull($coords);
    self::assertArrayHasKey('location', $errors);
  }
}
