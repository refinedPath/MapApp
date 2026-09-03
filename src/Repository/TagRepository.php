<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use App\Exception\TagNameAlreadyExistsException;
use App\ReadModel\TagView;
use Override;
use PDO;
use PDOException;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-type TagViewRow array{
 *   id: string,
 *   name: string,
 *   color: string,
 *   emoji: string|null,
 *   assignment_count: string
 * }
 */
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
      fn (array $row): Tag => TagHydrator::fromRow($row),
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

    return $row === false ? null : TagHydrator::fromRow($row);
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

  /**
   * @throws TagNameAlreadyExistsException
   */
  #[Override]
  public function update(
    Uuid $id,
    Uuid $userId,
    string $name,
    string $color,
    ?string $emoji,
    \DateTimeImmutable $updatedAt,
  ): int {
    $stmt = $this->pdo->prepare(
      'UPDATE tags
      SET name = :name, color = :color, emoji = :emoji, updated_at = :updated_at
      WHERE id = :id AND user_id = :user_id'
    );
    try {
      $stmt->execute([
        'id' => $id->toRfc4122(),
        'user_id' => $userId->toRfc4122(),
        'name' => $name,
        'color' => $color,
        'emoji' => $emoji,
        'updated_at' => $updatedAt->format('Y-m-d H:i:sP'),
      ]);
    } catch (PDOException $e) {
      if (($e->errorInfo[0] ?? null) === '23505') {
        throw new TagNameAlreadyExistsException('Tag name already exists.', 0, $e);
      }
      throw $e;
    }

    return $stmt->rowCount();
  }

  #[Override]
  public function delete(Uuid $id, Uuid $userId): int
  {
    $stmt = $this->pdo->prepare(
      'DELETE FROM tags WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute([
      'id' => $id->toRfc4122(),
      'user_id' => $userId->toRfc4122(),
    ]);

    return $stmt->rowCount();
  }

  /** @return TagView[] */
  #[Override]
  public function findAllForUserWithCounts(Uuid $userId): array
  {
    $stmt = $this->pdo->prepare(
      'SELECT t.id, t.name, t.color, t.emoji,
              COUNT(pt.place_id) AS assignment_count
      FROM tags t
      LEFT JOIN place_tags pt ON pt.tag_id = t.id
      WHERE t.user_id = :user_id
      GROUP BY t.id
      ORDER BY t.name ASC'
    );
    $stmt->execute(['user_id' => $userId->toRfc4122()]);

    /** @var list<TagViewRow> $rows */
    $rows = $stmt->fetchAll();

    return array_map(fn (array $row): TagView => $this->hydrateTagView($row), $rows);
  }

  /** @param TagViewRow $row */
  private function hydrateTagView(array $row): TagView
  {
    return new TagView(
      id: Uuid::fromString($row['id']),
      name: $row['name'],
      color: $row['color'],
      emoji: $row['emoji'],
      assignmentCount: (int) $row['assignment_count'],
    );
  }
}
