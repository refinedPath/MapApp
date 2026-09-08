<?php

declare(strict_types=1);

namespace App\Http;

use App\Entity\Tag;
use App\Validation\PasswordPolicy;

final class ConfigProvider
{
  public function __construct(
    private readonly PasswordPolicy $passwordPolicy,
  ) {
  }

  /**
   * Anonymous-safe app config (public GET /api/config).
   * Only non-sensitive fields any unauthenticated page may read.
   *
   * @return array<string, mixed>
   */
  public function publicConfig(): array
  {
    return [
      'password' => $this->passwordPolicy->toArray(),
    ];
  }

  /**
   * Per-user config superset (authed GET /api/config/me):
   * the public config plus fields that only make sense once signed in.
   *
   * @return array<string, mixed>
   */
  public function meConfig(): array
  {
    return $this->publicConfig() + [
      'tag' => [
        'default_color' => Tag::DEFAULT_COLOR,
      ],
    ];
  }
}
