<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Coordinates;
use App\Entity\Place;
use App\Http\PlaceSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PlaceSerializerTest extends TestCase
{
  public function testSerializesAllFieldsWithAtomDates(): void
  {
    $id = Uuid::v7();
    $created = new \DateTimeImmutable('2026-01-02T03:04:05+00:00');
    $updated = new \DateTimeImmutable('2026-02-03T04:05:06+00:00');

    $place = new Place(
      id: $id,
      userId: Uuid::v7(),
      name: 'Statue of Liberty',
      description: 'Been here once.',
      location: new Coordinates(40.6892, -74.0445),
      createdAt: $created,
      updatedAt: $updated,
    );

    self::assertSame([
      'id' => $id->toRfc4122(),
      'name' => 'Statue of Liberty',
      'description' => 'Been here once.',
      'latitude' => 40.6892,
      'longitude' => -74.0445,
      'created_at' => '2026-01-02T03:04:05+00:00',
      'updated_at' => '2026-02-03T04:05:06+00:00',
    ], PlaceSerializer::toArray($place));
  }

  public function testNullDescriptionPassesThrough(): void
  {
    $place = new Place(
      id: Uuid::v7(),
      userId: Uuid::v7(),
      name: 'No description',
      description: null,
      location: new Coordinates(0.0, 0.0),
      createdAt: new \DateTimeImmutable(),
      updatedAt: new \DateTimeImmutable(),
    );

    self::assertNull(PlaceSerializer::toArray($place)['description']);
  }
}
