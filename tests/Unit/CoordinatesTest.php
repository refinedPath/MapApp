<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Coordinates;
use App\Exception\InvalidCoordinatesException;
use PHPUnit\Framework\TestCase;

final class CoordinatesTest extends TestCase
{
  public function testAcceptsInRangeValues(): void
  {
    $c = new Coordinates(40.7128, -74.0060);

    self::assertSame(40.7128, $c->latitude);
    self::assertSame(-74.0060, $c->longitude);
  }

  public function testAcceptsBoundaryValues(): void
  {
    $c = new Coordinates(-90.0, 180.0);

    self::assertSame(-90.0, $c->latitude);
    self::assertSame(180.0, $c->longitude);
  }

  public function testRejectsLatitudeAbove90(): void
  {
    $this->expectException(InvalidCoordinatesException::class);
    new Coordinates(90.001, 0.0);
  }

  public function testRejectsLatitudeBelowMinus90(): void
  {
    $this->expectException(InvalidCoordinatesException::class);
    new Coordinates(-90.001, 0.0);
  }

  public function testRejectsLongitudeAbove180(): void
  {
    $this->expectException(InvalidCoordinatesException::class);
    new Coordinates(0.0, 180.001);
  }

  public function testRejectsLongitudeBelowMinus180(): void
  {
    $this->expectException(InvalidCoordinatesException::class);
    new Coordinates(0.0, -180.001);
  }
}
