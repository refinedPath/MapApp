<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\InvalidTokenException;
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

  public function verifyToken(string $token): Uuid
  {
    try {
      $decoded = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
    } catch (\Throwable $e) {
      throw new InvalidTokenException('Invalid or expired token.', 0, $e);
    }

    if (!isset($decoded->sub) || !is_string($decoded->sub)) {
      throw new InvalidTokenException('Token is missing a valid subject.');
    }

    try {
      return Uuid::fromString($decoded->sub);
    } catch (\InvalidArgumentException $e) {
      throw new InvalidTokenException('Token subject is not a valid identifier.', 0, $e);
    }
  }
}
