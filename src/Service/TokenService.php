<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\Uid\Uuid;

final class TokenService
{
  private const ALGORITHM = 'HS256';

  public function __construct(
    private readonly string $secret,
    private readonly int $ttlSeconds,
  ) {}

  public function issueForUser(Uuid $userId): string
  {
    $now = time();
    $payload = [
      'sub' => $userId->toRfc4122(),      // subject
      'iat' => $now,                      // issued at
      'exp' => $now + $this->ttlSeconds,  // expiry
    ];

    return JWT::encode($payload, $this->secret, self::ALGORITHM);
  }
}
