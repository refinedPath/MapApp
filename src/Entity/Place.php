<?php

declare(strict_types=1);

namespace App\Entity;

use Symfony\Component\Uid\Uuid;

final class Place
{
  public const int MAX_NAME_LENGTH = 255;

  public function __construct(
    public readonly Uuid $id,
    public readonly Uuid $userId,
    public readonly string $name,
    public readonly ?string $description,
    public readonly Coordinates $location,
    public readonly \DateTimeImmutable $createdAt,
    public readonly \DateTimeImmutable $updatedAt,
  ) {}
}
