<?php

declare(strict_types=1);

namespace App\Entity;

use Symfony\Component\Uid\Uuid;

final readonly class Tag
{
  public const int MAX_NAME_LENGTH = 255;
  public const string DEFAULT_COLOR = '#525f7a';

  public function __construct(
    public Uuid $id,
    public Uuid $userId,
    public string $name,
    public string $color,
    public ?string $emoji,
    public \DateTimeImmutable $createdAt,
    public \DateTimeImmutable $updatedAt,
  ) {
  }
}
