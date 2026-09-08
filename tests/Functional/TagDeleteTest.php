<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class TagDeleteTest extends FunctionalTestCase
{
  public function testDeletesOwnTag(): void
  {
    $user = $this->fixtures->createUser();
    $tagId = $this->fixtures->createTag($user['id'], 'coffee');

    $response = $this->request('DELETE', "/api/tags/{$tagId->toRfc4122()}", null, $this->authHeader($user['id']));

    self::assertSame(204, $response->getStatusCode());

    $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM tags WHERE id = :id');
    $stmt->execute(['id' => $tagId->toRfc4122()]);
    self::assertSame(0, (int) $stmt->fetchColumn());
  }

  public function testDeleteCascadesAndClearsPrimaryButKeepsPlace(): void
  {
    $user = $this->fixtures->createUser();
    $tagId = $this->fixtures->createTag($user['id'], 'coffee');
    $placeId = $this->fixtures->createPlace($user['id'], 'Cafe');
    $this->fixtures->assignTag($placeId, $tagId);

    // Make this tag the place's primary.
    $setPrimary = $this->pdo->prepare('UPDATE places SET primary_tag_id = :tag WHERE id = :place');
    $setPrimary->execute(['tag' => $tagId->toRfc4122(), 'place' => $placeId->toRfc4122()]);

    $response = $this->request('DELETE', "/api/tags/{$tagId->toRfc4122()}", null, $this->authHeader($user['id']));
    self::assertSame(204, $response->getStatusCode());

    // Junction rows cascade away (place_tags FK: ON DELETE CASCADE).
    $junction = $this->pdo->prepare('SELECT COUNT(*) FROM place_tags WHERE tag_id = :tag');
    $junction->execute(['tag' => $tagId->toRfc4122()]);
    self::assertSame(0, (int) $junction->fetchColumn());

    // The place survives — the cascade takes the link, never the entity.
    $survives = $this->pdo->prepare('SELECT COUNT(*) FROM places WHERE id = :place');
    $survives->execute(['place' => $placeId->toRfc4122()]);
    self::assertSame(1, (int) $survives->fetchColumn());

    // Its primary pointer is nulled, not left dangling (primary_tag_id FK: ON DELETE SET NULL).
    $primary = $this->pdo->prepare('SELECT primary_tag_id FROM places WHERE id = :place');
    $primary->execute(['place' => $placeId->toRfc4122()]);
    self::assertNull($primary->fetchColumn());
  }

  public function testCannotDeleteAnotherUsersTag(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');
    $tagId = $this->fixtures->createTag($owner['id'], 'coffee');

    $response = $this->request('DELETE', "/api/tags/{$tagId->toRfc4122()}", null, $this->authHeader($attacker['id']));

    self::assertSame(404, $response->getStatusCode());

    $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM tags WHERE id = :id');
    $stmt->execute(['id' => $tagId->toRfc4122()]);
    self::assertSame(1, (int) $stmt->fetchColumn());
  }
}
