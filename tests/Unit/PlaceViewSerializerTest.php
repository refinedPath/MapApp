<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Http\PlaceViewSerializer;
use App\ReadModel\PlaceView;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PlaceViewSerializerTest extends TestCase
{
  public function testSerializesWithPrimaryTagFields(): void
  {
    $id = Uuid::v7();
    $tagId = Uuid::v7();
    $view = new PlaceView(
      id: $id,
      name: 'Cafe',
      description: 'Nice',
      latitude: 1.5,
      longitude: 2.5,
      primaryTagId: $tagId,
      primaryColor: '#8a2be2',
      primaryEmoji: '☕',
      createdAt: new \DateTimeImmutable('2026-01-02T03:04:05+00:00'),
      updatedAt: new \DateTimeImmutable('2026-01-02T03:04:05+00:00'),
    );

    $out = PlaceViewSerializer::toArray($view);

    self::assertSame($id->toRfc4122(), $out['id']);
    self::assertSame($tagId->toRfc4122(), $out['primary_tag_id']);
    self::assertSame('#8a2be2', $out['primary_color']);
    self::assertSame('☕', $out['primary_emoji']);
    self::assertSame('2026-01-02T03:04:05+00:00', $out['created_at']);
  }

  public function testNullPrimaryTagFieldsSerializeAsNull(): void
  {
    $view = new PlaceView(
      id: Uuid::v7(),
      name: 'Untagged',
      description: null,
      latitude: 0.0,
      longitude: 0.0,
      primaryTagId: null,
      primaryColor: null,
      primaryEmoji: null,
      createdAt: new \DateTimeImmutable(),
      updatedAt: new \DateTimeImmutable(),
    );

    $out = PlaceViewSerializer::toArray($view);

    self::assertNull($out['primary_tag_id']);
    self::assertNull($out['primary_color']);
    self::assertNull($out['primary_emoji']);
    self::assertNull($out['description']);
  }
}
