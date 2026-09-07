<?php

declare(strict_types=1);

namespace App\Repository;

use Override;
use PDO;
use Symfony\Component\Uid\Uuid;

final class EmailVerificationTokenRepository implements EmailVerificationTokenRepositoryInterface
{
  public function __construct(
    private readonly PDO $pdo,
  ) {
  }

  #[Override]
  public function create(Uuid $userId, string $tokenHash, \DateTimeImmutable $expiresAt): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO email_verification_tokens (token_hash, user_id, expires_at)
       VALUES (:token_hash, :user_id, :expires_at)'
    );
    $stmt->execute([
      'token_hash' => $tokenHash,
      'user_id' => $userId->toRfc4122(),
      'expires_at' => $expiresAt->format('Y-m-d H:i:sP'),
    ]);
  }

  /**
   * @return array{user_id: string, expires_at: string}|null
   */
  #[Override]
  public function findByHash(string $tokenHash): ?array
  {
    $stmt = $this->pdo->prepare(
      'SELECT user_id, expires_at FROM email_verification_tokens WHERE token_hash = :token_hash'
    );
    $stmt->execute(['token_hash' => $tokenHash]);

    /** @var array{user_id: string, expires_at: string}|false $row */
    $row = $stmt->fetch();

    return $row === false ? null : $row;
  }

  #[Override]
  public function deleteForUser(Uuid $userId): void
  {
    $stmt = $this->pdo->prepare(
      'DELETE FROM email_verification_tokens WHERE user_id = :user_id'
    );
    $stmt->execute(['user_id' => $userId->toRfc4122()]);
  }

  #[Override]
  public function existsForUserSince(Uuid $userId, \DateTimeImmutable $since): bool
  {
    $stmt = $this->pdo->prepare(
      'SELECT 1 FROM email_verification_tokens
       WHERE user_id = :user_id AND created_at >= :since LIMIT 1'
    );
    $stmt->execute([
      'user_id' => $userId->toRfc4122(),
      'since' => $since->format('Y-m-d H:i:sP'),
    ]);

    return $stmt->fetchColumn() !== false;
  }
}
