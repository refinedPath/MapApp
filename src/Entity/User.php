<?php

declare(strict_types=1);

namespace App\Entity;

use Symfony\Component\Uid\Uuid;

final class User
{
  public function __construct(
    public readonly Uuid $id,
    public readonly string $email,
    public readonly string $passwordHash,
    public readonly \DateTimeImmutable $createdAt,
    public readonly \DateTimeImmutable $updatedAt,
  ) {}
}
