<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class UnassignTagTest extends FunctionalTestCase
{
  public function testUnassignsTagFromOwnPlace(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);
    $tagId = $this->fixtures->createTag($user['id'], 'coffee');
    $this->fixtures->assignTag($placeId, $tagId);

    $response = $this->request(
      'DELETE',
      "/api/places/{$placeId->toRfc4122()}/tags/{$tagId->toRfc4122()}",
      null,
      $this->authHeader($user['id']),
    );

    self::assertSame(204, $response->getStatusCode());

    $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM place_tags WHERE place_id = :p AND tag_id = :t');
    $stmt->execute(['p' => $placeId->toRfc4122(), 't' => $tagId->toRfc4122()]);
    self::assertSame(0, (int) $stmt->fetchColumn());
  }

  public function testUnassignIsIdempotent(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);
    $tagId = $this->fixtures->createTag($user['id'], 'coffee');

    $response = $this->request(
      'DELETE',
      "/api/places/{$placeId->toRfc4122()}/tags/{$tagId->toRfc4122()}",
      null,
      $this->authHeader($user['id']),
    );

    self::assertSame(204, $response->getStatusCode());
  }

  public function testCannotUnassignFromAnotherUsersPlace(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');
    $placeId = $this->fixtures->createPlace($owner['id']);
    $tagId = $this->fixtures->createTag($owner['id'], 'coffee');
    $this->fixtures->assignTag($placeId, $tagId);

    $response = $this->request(
      'DELETE',
      "/api/places/{$placeId->toRfc4122()}/tags/{$tagId->toRfc4122()}",
      null,
      $this->authHeader($attacker['id']),
    );

    self::assertSame(404, $response->getStatusCode());

    $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM place_tags WHERE place_id = :p AND tag_id = :t');
    $stmt->execute(['p' => $placeId->toRfc4122(), 't' => $tagId->toRfc4122()]);
    self::assertSame(1, (int) $stmt->fetchColumn());
  }

  public function testUnassigningPrimaryTagClearsPrimary(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);
    $tagId = $this->fixtures->createTag($user['id'], 'coffee');
    $this->fixtures->assignTag($placeId, $tagId);

    $setPrimary = $this->pdo->prepare('UPDATE places SET primary_tag_id = :tag WHERE id = :place');
    $setPrimary->execute(['tag' => $tagId->toRfc4122(), 'place' => $placeId->toRfc4122()]);

    $response = $this->request(
      'DELETE',
      "/api/places/{$placeId->toRfc4122()}/tags/{$tagId->toRfc4122()}",
      null,
      $this->authHeader($user['id']),
    );
    self::assertSame(204, $response->getStatusCode());

    $junction = $this->pdo->prepare('SELECT COUNT(*) FROM place_tags WHERE place_id = :p AND tag_id = :t');
    $junction->execute(['p' => $placeId->toRfc4122(), 't' => $tagId->toRfc4122()]);
    self::assertSame(0, (int) $junction->fetchColumn());

    $primary = $this->pdo->prepare('SELECT primary_tag_id FROM places WHERE id = :place');
    $primary->execute(['place' => $placeId->toRfc4122()]);
    self::assertNull($primary->fetchColumn());
  }

  public function testUnassigningNonPrimaryTagLeavesPrimaryIntact(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);
    $primaryTag = $this->fixtures->createTag($user['id'], 'coffee');
    $otherTag = $this->fixtures->createTag($user['id'], 'tea');
    $this->fixtures->assignTag($placeId, $primaryTag);
    $this->fixtures->assignTag($placeId, $otherTag);

    $setPrimary = $this->pdo->prepare('UPDATE places SET primary_tag_id = :tag WHERE id = :place');
    $setPrimary->execute(['tag' => $primaryTag->toRfc4122(), 'place' => $placeId->toRfc4122()]);

    $response = $this->request(
      'DELETE',
      "/api/places/{$placeId->toRfc4122()}/tags/{$otherTag->toRfc4122()}",
      null,
      $this->authHeader($user['id']),
    );
    self::assertSame(204, $response->getStatusCode());

    $primary = $this->pdo->prepare('SELECT primary_tag_id FROM places WHERE id = :place');
    $primary->execute(['place' => $placeId->toRfc4122()]);
    self::assertSame($primaryTag->toRfc4122(), $primary->fetchColumn());
  }
}
