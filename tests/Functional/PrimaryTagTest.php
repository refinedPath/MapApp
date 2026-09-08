<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class PrimaryTagTest extends FunctionalTestCase
{
  public function testSetsPrimaryTagWhenAssigned(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);
    $tagId = $this->fixtures->createTag($user['id'], 'coffee');
    $this->fixtures->assignTag($placeId, $tagId);

    $response = $this->request(
      'PUT',
      "/api/places/{$placeId->toRfc4122()}/primary-tag/{$tagId->toRfc4122()}",
      null,
      $this->authHeader($user['id']),
    );

    self::assertSame(204, $response->getStatusCode());

    $stmt = $this->pdo->prepare('SELECT primary_tag_id FROM places WHERE id = :place');
    $stmt->execute(['place' => $placeId->toRfc4122()]);
    self::assertSame($tagId->toRfc4122(), $stmt->fetchColumn());
  }

  public function testRejectsPrimaryTagNotAssignedToPlace(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);
    $tagId = $this->fixtures->createTag($user['id'], 'coffee');

    $response = $this->request(
      'PUT',
      "/api/places/{$placeId->toRfc4122()}/primary-tag/{$tagId->toRfc4122()}",
      null,
      $this->authHeader($user['id']),
    );

    self::assertSame(409, $response->getStatusCode());

    $stmt = $this->pdo->prepare('SELECT primary_tag_id FROM places WHERE id = :place');
    $stmt->execute(['place' => $placeId->toRfc4122()]);
    self::assertNull($stmt->fetchColumn());
  }

  public function testCannotSetPrimaryOnAnotherUsersPlace(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');
    $placeId = $this->fixtures->createPlace($owner['id']);
    $tagId = $this->fixtures->createTag($owner['id'], 'coffee');
    $this->fixtures->assignTag($placeId, $tagId);

    $response = $this->request(
      'PUT',
      "/api/places/{$placeId->toRfc4122()}/primary-tag/{$tagId->toRfc4122()}",
      null,
      $this->authHeader($attacker['id']),
    );

    self::assertSame(404, $response->getStatusCode());
  }

  public function testCannotSetAnotherUsersTagAsPrimary(): void
  {
    $user = $this->fixtures->createUser();
    $other = $this->fixtures->createUser('other@mapapp.test');
    $placeId = $this->fixtures->createPlace($user['id']);
    $otherTag = $this->fixtures->createTag($other['id'], 'coffee');

    $response = $this->request(
      'PUT',
      "/api/places/{$placeId->toRfc4122()}/primary-tag/{$otherTag->toRfc4122()}",
      null,
      $this->authHeader($user['id']),
    );

    self::assertSame(404, $response->getStatusCode());
  }

  public function testClearsPrimaryTag(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);
    $tagId = $this->fixtures->createTag($user['id'], 'coffee');
    $this->fixtures->assignTag($placeId, $tagId);

    $setPrimary = $this->pdo->prepare('UPDATE places SET primary_tag_id = :tag WHERE id = :place');
    $setPrimary->execute(['tag' => $tagId->toRfc4122(), 'place' => $placeId->toRfc4122()]);

    $response = $this->request(
      'DELETE',
      "/api/places/{$placeId->toRfc4122()}/primary-tag",
      null,
      $this->authHeader($user['id']),
    );

    self::assertSame(204, $response->getStatusCode());

    $stmt = $this->pdo->prepare('SELECT primary_tag_id FROM places WHERE id = :place');
    $stmt->execute(['place' => $placeId->toRfc4122()]);
    self::assertNull($stmt->fetchColumn());
  }

  public function testCannotClearPrimaryOnAnotherUsersPlace(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');
    $placeId = $this->fixtures->createPlace($owner['id']);

    $response = $this->request(
      'DELETE',
      "/api/places/{$placeId->toRfc4122()}/primary-tag",
      null,
      $this->authHeader($attacker['id']),
    );

    self::assertSame(404, $response->getStatusCode());
  }
}
