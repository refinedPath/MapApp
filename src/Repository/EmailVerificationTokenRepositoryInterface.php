<?php

declare(strict_types=1);

namespace App\Repository;

use Symfony\Component\Uid\Uuid;

interface EmailVerificationTokenRepositoryInterface
{
  public function create(Uuid $userId, string $tokenHash, \DateTimeImmutable $expiresAt): void;

  /**
   * Returns the row for this hash regardless of expiry (so the caller can
   * distinguish "not found" from "expired"), or null if no such hash.
   *
   * @return array{user_id: string, expires_at: string}|null
   */
  public function findByHash(string $tokenHash): ?array;

  public function deleteForUser(Uuid $userId): void;

  public function existsForUserSince(Uuid $userId, \DateTimeImmutable $since): bool;
}
