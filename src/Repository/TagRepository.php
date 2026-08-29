<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use App\Exception\TagNameAlreadyExistsException;
use Override;
use PDO;
use PDOException;
use Symfony\Component\Uid\Uuid;

final class TagRepository implements TagRepositoryInterface
{
  public function __construct(
    private readonly PDO $pdo,
  ) {
  }

  /** @return Tag[] */
  #[Override]
  public function findAllForUser(Uuid $userId): array
  {
    $stmt = $this->pdo->prepare(
      'SELECT id, user_id, name, color, emoji, created_at, updated_at
      FROM tags
      WHERE user_id = :user_id
      ORDER BY name ASC'
    );
    $stmt->execute([
      'user_id' => $userId->toRfc4122(),
    ]);

    /** @var list<array<string, string>> $rows */
    $rows = $stmt->fetchAll();

    return array_map(
      fn (array $row): Tag => $this->hydrate($row),
      $rows
    );
  }

  #[Override]
  public function findByIdForUser(Uuid $id, Uuid $userId): ?Tag
  {
    $stmt = $this->pdo->prepare(
      'SELECT id, user_id, name, color, emoji, created_at, updated_at
      FROM tags
      WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute([
      'id' => $id->toRfc4122(),
      'user_id' => $userId->toRfc4122(),
    ]);

    /** @var array<string, string>|false $row */
    $row = $stmt->fetch();

    return $row === false ? null : $this->hydrate($row);
  }

  /**
   * @throws TagNameAlreadyExistsException
   */
  #[Override]
  public function create(Tag $tag): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO tags (id, user_id, name, color, emoji, created_at, updated_at)
      VALUES (:id, :user_id, :name, :color, :emoji, :created_at, :updated_at)'
    );
    try {
      $stmt->execute([
        'id' => $tag->id->toRfc4122(),
        'user_id' => $tag->userId->toRfc4122(),
        'name' => $tag->name,
        'color' => $tag->color,
        'emoji' => $tag->emoji,
        'created_at' => $tag->createdAt->format('Y-m-d H:i:sP'),
        'updated_at' => $tag->updatedAt->format('Y-m-d H:i:sP'),
      ]);
    } catch (PDOException $e) {
      if (($e->errorInfo[0] ?? null) === '23505') {
        throw new TagNameAlreadyExistsException('Tag name already exists.', 0, $e);
      }
      throw $e;
    }
  }

  /** @param array<string, string> $row */
  private function hydrate(array $row): Tag
  {
    return new Tag(
      id: Uuid::fromString($row['id']),
      userId: Uuid::fromString($row['user_id']),
      name: $row['name'],
      color: $row['color'],
      emoji: $row['emoji'],
      createdAt: new \DateTimeImmutable($row['created_at']),
      updatedAt: new \DateTimeImmutable($row['updated_at']),
    );
  }
}
