<?php

declare(strict_types=1);

namespace App\ReadModel;

use Symfony\Component\Uid\Uuid;

final readonly class PlaceView
{
  public function __construct(
    public Uuid $id,
    public string $name,
    public ?string $description,
    public float $latitude,
    public float $longitude,
    public ?Uuid $primaryTagId,
    public ?string $primaryColor,
    public ?string $primaryEmoji,
    public \DateTimeImmutable $createdAt,
    public \DateTimeImmutable $updatedAt,
  ) {
  }
}
