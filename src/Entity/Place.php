<?php

declare(strict_types=1);

namespace App\Entity;

use Symfony\Component\Uid\Uuid;

final readonly class Place
{
  public const int MAX_NAME_LENGTH = 255;

  public function __construct(
    public Uuid $id,
    public Uuid $userId,
    public string $name,
    public ?string $description,
    public Coordinates $location,
    public \DateTimeImmutable $createdAt,
    public \DateTimeImmutable $updatedAt,
  ) {
  }
}
