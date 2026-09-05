<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PDO;
use Symfony\Component\Uid\Uuid;

final class Fixtures
{
  public function __construct(private readonly PDO $pdo) {}

  /** @return array{id: Uuid, email: string, password: string} */
  public function createUser(string $email = 'user@mapapp.test', string $password = 'password123'): array
  {
    $id = Uuid::v7();
    $stmt = $this->pdo->prepare(
      'INSERT INTO users (id, email, password_hash) VALUES (:id, :email, :hash)'
    );
    $stmt->execute([
      'id' => $id->toRfc4122(),
      'email' => $email,
      'hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    return ['id' => $id, 'email' => $email, 'password' => $password];
  }

  public function createTag(Uuid $userId, string $name, string $color = '#8a2be2', ?string $emoji = null): Uuid
  {
    $id = Uuid::v7();
    $stmt = $this->pdo->prepare(
      'INSERT INTO tags (id, user_id, name, color, emoji) VALUES (:id, :user_id, :name, :color, :emoji)'
    );
    $stmt->execute([
      'id' => $id->toRfc4122(),
      'user_id' => $userId->toRfc4122(),
      'name' => $name,
      'color' => $color,
      'emoji' => $emoji,
    ]);

    return $id;
  }

  public function createPlace(Uuid $userId, string $name = 'Test Place', float $lat = 40.0, float $lng = -74.0): Uuid
  {
    $id = Uuid::v7();
    $stmt = $this->pdo->prepare(
      'INSERT INTO places (id, user_id, name, location)
       VALUES (:id, :user_id, :name, ST_SetSRID(ST_MakePoint(:lng, :lat), 4326)::geography)'
    );
    $stmt->execute([
      'id' => $id->toRfc4122(),
      'user_id' => $userId->toRfc4122(),
      'name' => $name,
      'lng' => $lng,
      'lat' => $lat,
    ]);

    return $id;
  }

  public function assignTag(Uuid $placeId, Uuid $tagId): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO place_tags (place_id, tag_id) VALUES (:place_id, :tag_id)'
    );
    $stmt->execute(['place_id' => $placeId->toRfc4122(), 'tag_id' => $tagId->toRfc4122()]);
  }
}
