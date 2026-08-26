<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Exception\EmailAlreadyExistsException;
use Override;
use PDO;
use Symfony\Component\Uid\Uuid;

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
      'SELECT id, email, password_hash, created_at, updated_at
      FROM users WHERE email = :email'
    );
    $stmt->execute(['email' => $email]);

    /** @var array<string, string>|false $row */
    $row = $stmt->fetch();

    return $row === false ? null : $this->hydrate($row);
  }

  #[Override]
  public function findById(Uuid $id): ?User
  {
    $stmt = $this->pdo->prepare(
      'SELECT id, email, password_hash, created_at, updated_at
      FROM users WHERE id = :id'
    );
    $stmt->execute(['id' => $id->toRfc4122()]);

    /** @var array<string, string>|false $row */
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

  /**
   * @param array<string, string> $row
   */
  private function hydrate(array $row): User
  {
    return new User(
      id: Uuid::fromString($row['id']),
      email: $row['email'],
      passwordHash: $row['password_hash'],
      createdAt: new \DateTimeImmutable($row['created_at']),
      updatedAt: new \DateTimeImmutable($row['updated_at']),
    );
  }
}
