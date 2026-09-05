<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Exception\InvalidTokenException;
use App\Service\TokenService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class TokenServiceTest extends TestCase
{
  private const SECRET = 'test-secret-that-is-definitely-32+-bytes-long';
  private const TTL = 3600;

  public function testConstructorRejectsSecretShorterThan32Bytes(): void
  {
    $this->expectException(\InvalidArgumentException::class);

    new TokenService('too-short', self::TTL, 'mapapp', 'mapapp');
  }

  public function testIssuedTokenVerifiesBackToTheSameUser(): void
  {
    $service = new TokenService(self::SECRET, self::TTL, 'mapapp', 'mapapp');
    $userId = Uuid::v7();

    $token = $service->issueForUser($userId);

    self::assertTrue($userId->equals($service->verifyToken($token)));
  }

  public function testVerifyRejectsTokenWithWrongAudience(): void
  {
    // Same secret (valid signature), but the issuer stamped a different audience.
    $issuer = new TokenService(self::SECRET, self::TTL, 'mapapp', 'someone-else');
    $verifier = new TokenService(self::SECRET, self::TTL, 'mapapp', 'mapapp');
    $token = $issuer->issueForUser(Uuid::v7());

    $this->expectException(InvalidTokenException::class);

    $verifier->verifyToken($token);
  }
}
