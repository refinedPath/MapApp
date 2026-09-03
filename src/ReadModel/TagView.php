<?php

declare(strict_types=1);

namespace App\ReadModel;

use Symfony\Component\Uid\Uuid;

final readonly class TagView
{
  public function __construct(
    public Uuid $id,
    public string $name,
    public string $color,
    public ?string $emoji,
    public int $assignmentCount,
  ) {
  }
}
