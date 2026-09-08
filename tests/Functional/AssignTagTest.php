<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class AssignTagTest extends FunctionalTestCase
{
  public function testAssignsTagToOwnPlace(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);
    $tagId = $this->fixtures->createTag($user['id'], 'coffee');

    $response = $this->request(
      'PUT',
      "/api/places/{$placeId->toRfc4122()}/tags/{$tagId->toRfc4122()}",
      null,
      $this->authHeader($user['id']),
    );

    self::assertSame(204, $response->getStatusCode());

    $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM place_tags WHERE place_id = :p AND tag_id = :t');
    $stmt->execute(['p' => $placeId->toRfc4122(), 't' => $tagId->toRfc4122()]);
    self::assertSame(1, (int) $stmt->fetchColumn());
  }

  public function testReassignIsIdempotent(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);
    $tagId = $this->fixtures->createTag($user['id'], 'coffee');
    $path = "/api/places/{$placeId->toRfc4122()}/tags/{$tagId->toRfc4122()}";

    $first = $this->request('PUT', $path, null, $this->authHeader($user['id']));
    self::assertSame(204, $first->getStatusCode());

    $second = $this->request('PUT', $path, null, $this->authHeader($user['id']));
    self::assertSame(204, $second->getStatusCode());

    $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM place_tags WHERE place_id = :p AND tag_id = :t');
    $stmt->execute(['p' => $placeId->toRfc4122(), 't' => $tagId->toRfc4122()]);
    self::assertSame(1, (int) $stmt->fetchColumn());
  }

  public function testCannotAssignToAnotherUsersPlace(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');
    $placeId = $this->fixtures->createPlace($owner['id']);
    $attackerTag = $this->fixtures->createTag($attacker['id'], 'coffee');

    $response = $this->request(
      'PUT',
      "/api/places/{$placeId->toRfc4122()}/tags/{$attackerTag->toRfc4122()}",
      null,
      $this->authHeader($attacker['id']),
    );

    self::assertSame(404, $response->getStatusCode());
  }

  public function testCannotAssignAnotherUsersTag(): void
  {
    $user = $this->fixtures->createUser();
    $other = $this->fixtures->createUser('other@mapapp.test');
    $placeId = $this->fixtures->createPlace($user['id']);
    $otherTag = $this->fixtures->createTag($other['id'], 'coffee');

    $response = $this->request(
      'PUT',
      "/api/places/{$placeId->toRfc4122()}/tags/{$otherTag->toRfc4122()}",
      null,
      $this->authHeader($user['id']),
    );

    self::assertSame(404, $response->getStatusCode());

    $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM place_tags WHERE place_id = :p AND tag_id = :t');
    $stmt->execute(['p' => $placeId->toRfc4122(), 't' => $otherTag->toRfc4122()]);
    self::assertSame(0, (int) $stmt->fetchColumn());
  }
}
