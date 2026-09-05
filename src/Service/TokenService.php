<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\InvalidTokenException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class TokenService
{
  private const ALGORITHM = 'HS256';
  private const MIN_SECRET_BYTES = 32;  // 256-bit floor for HS256
  private const LEEWAY_SECONDS = 30;    // tolerate small clock skew

  public function __construct(
    private readonly string $secret,
    private readonly int $ttlSeconds,
    private readonly string $issuer,
    private readonly string $audience,
  ) {
    if (strlen($secret) < self::MIN_SECRET_BYTES) {
      throw new InvalidArgumentException(
        'JWT secret must be at least ' . self::MIN_SECRET_BYTES . ' bytes (256 bits) for HS256.'
      );
    }
  }

  public function issueForUser(Uuid $userId): string
  {
    $now = time();
    $payload = [
      'iss' => $this->issuer,             // issuer
      'aud' => $this->audience,            // audience
      'sub' => $userId->toRfc4122(),      // subject
      'iat' => $now,                      // issued at
      'exp' => $now + $this->ttlSeconds,  // expiry
    ];

    return JWT::encode($payload, $this->secret, self::ALGORITHM);
  }

  public function verifyToken(string $token): Uuid
  {
    JWT::$leeway = self::LEEWAY_SECONDS;

    try {
      $decoded = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
    } catch (\Throwable $e) {
      throw new InvalidTokenException('Invalid or expired token.', 0, $e);
    }

    if (($decoded->iss ?? null) !== $this->issuer) {
      throw new InvalidTokenException('Token issuer is not recognized.');
    }

    if (($decoded->aud ?? null) !== $this->audience) {
      throw new InvalidTokenException('Token audience is not recognized.');
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
