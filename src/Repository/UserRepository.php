<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Exception\EmailAlreadyExistsException;
use Override;
use PDO;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-type UserRow array{
 *   id: string,
 *   email: string,
 *   password_hash: string,
 *   created_at: string,
 *   updated_at: string,
 *   email_verified_at: string|null
 * }
 */
final class UserRepository implements UserRepositoryInterface
{
  public function __construct(
    private readonly PDO $pdo,
  ) {
  }

  #[Override]
  public function findByEmail(string $email): ?User
  {
    $stmt = $this->pdo->prepare(
      'SELECT id, email, password_hash, created_at, updated_at, email_verified_at
      FROM users WHERE email = :email'
    );
    $stmt->execute(['email' => $email]);

    /** @var UserRow|false $row */
    $row = $stmt->fetch();

    return $row === false ? null : $this->hydrate($row);
  }

  #[Override]
  public function findById(Uuid $id): ?User
  {
    $stmt = $this->pdo->prepare(
      'SELECT id, email, password_hash, created_at, updated_at, email_verified_at
      FROM users WHERE id = :id'
    );
    $stmt->execute(['id' => $id->toRfc4122()]);

    /** @var UserRow|false $row */
    $row = $stmt->fetch();

    return $row === false ? null : $this->hydrate($row);
  }

  /**
   * @throws EmailAlreadyExistsException
   */
  #[Override]
  public function create(User $user): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO users (id, email, password_hash, created_at, updated_at)
      VALUES (:id, :email, :password_hash, :created_at, :updated_at)'
    );
    try {
      $stmt->execute([
        'id' => $user->id->toRfc4122(),
        'email' => $user->email,
        'password_hash' => $user->passwordHash,
        'created_at' => $user->createdAt->format('Y-m-d H:i:sP'),
        'updated_at' => $user->updatedAt->format('Y-m-d H:i:sP'),
      ]);
    } catch (\PDOException $e) {
      if (($e->errorInfo[0] ?? null) === '23505') {
        throw new EmailAlreadyExistsException("Email already registered.", 0, $e);
      }
      throw $e;
    }
  }

  #[Override]
  public function markEmailVerified(Uuid $userId, \DateTimeImmutable $verifiedAt): void
  {
    $stmt = $this->pdo->prepare(
      'UPDATE users
        SET email_verified_at = :verified_at, updated_at = :updated_at
      WHERE id = :id'
    );
    $stmt->execute([
      'id' => $userId->toRfc4122(),
      'verified_at' => $verifiedAt->format('Y-m-d H:i:sP'),
      'updated_at' => $verifiedAt->format('Y-m-d H:i:sP'),
    ]);
  }

  #[Override]
  public function updatePasswordHash(Uuid $userId, string $passwordHash): void
  {
    $stmt = $this->pdo->prepare(
      'UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id'
    );
    $stmt->execute([
      'id' => $userId->toRfc4122(),
      'password_hash' => $passwordHash,
      'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:sP'),
    ]);
  }

  /**
   * @param UserRow $row
   */
  private function hydrate(array $row): User
  {
    return new User(
      id: Uuid::fromString($row['id']),
      email: $row['email'],
      passwordHash: $row['password_hash'],
      createdAt: new \DateTimeImmutable($row['created_at']),
      updatedAt: new \DateTimeImmutable($row['updated_at']),
      emailVerifiedAt: $row['email_verified_at'] === null ? null : new \DateTimeImmutable($row['email_verified_at']),
    );
  }
}
