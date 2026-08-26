<?php

declare(strict_types=1);

namespace App\Entity;

use Symfony\Component\Uid\Uuid;

final readonly class User
{
  public function __construct(
    public Uuid $id,
    public string $email,
    public string $passwordHash,
    public \DateTimeImmutable $createdAt,
    public \DateTimeImmutable $updatedAt,
  ) {
  }
}
