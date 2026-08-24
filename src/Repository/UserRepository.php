<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use PDO;
use Symfony\Component\Uid\Uuid;

final class UserRepository implements UserRepositoryInterface
{
  public function __construct(
    private readonly PDO $pdo,
  ) {}

  public function findByEmail(string $email): ?User
  {
    $stmt = $this->pdo->prepare(
      'SELECT id, email, password_hash, created_at, updated_at
      FROM users WHERE email = :email'
    );
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();

    return $row === false ? null : $this->hydrate($row);
  }

  public function findById(Uuid $id): ?User
  {
    $stmt = $this->pdo->prepare(
      'SELECT id, email, password_hash, created_at, updated_at
      FROM users WHERE id = :id'
    );
    $stmt->execute(['id' => $id->toRfc4122()]);
    $row = $stmt->fetch();

    return $row === false ? null : $this->hydrate($row);
  }

  public function create(User $user): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO users (id, email, password_hash, created_at, updated_at)
      VALUES (:id, :email, :password_hash, :created_at, :updated_at)'
    );
    $stmt->execute([
      'id' => $user->id->toRfc4122(),
      'email' => $user->email,
      'password_hash' => $user->passwordHash,
      'created_at' => $user->createdAt->format('Y-m-d H:i:sP'),
      'updated_at' => $user->updatedAt->format('Y-m-d H:i:sP'),
    ]);
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
