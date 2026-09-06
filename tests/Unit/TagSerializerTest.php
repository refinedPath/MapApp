<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Tag;
use App\Http\TagSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class TagSerializerTest extends TestCase
{
  public function testSerializesAllFields(): void
  {
    $id = Uuid::v7();
    $tag = new Tag(
      id: $id,
      userId: Uuid::v7(),
      name: 'coffee',
      color: '#8a2be2',
      emoji: '☕',
      createdAt: new \DateTimeImmutable('2026-01-02T03:04:05+00:00'),
      updatedAt: new \DateTimeImmutable('2026-01-02T03:04:05+00:00'),
    );

    self::assertSame([
      'id' => $id->toRfc4122(),
      'name' => 'coffee',
      'color' => '#8a2be2',
      'emoji' => '☕',
      'created_at' => '2026-01-02T03:04:05+00:00',
      'updated_at' => '2026-01-02T03:04:05+00:00',
    ], TagSerializer::toArray($tag));
  }

  public function testNullEmojiPassesThrough(): void
  {
    $tag = new Tag(
      id: Uuid::v7(),
      userId: Uuid::v7(),
      name: 'no-emoji',
      color: '#525f7a',
      emoji: null,
      createdAt: new \DateTimeImmutable(),
      updatedAt: new \DateTimeImmutable(),
    );

    self::assertNull(TagSerializer::toArray($tag)['emoji']);
  }
}
