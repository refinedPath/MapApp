<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class ListPlaceTagsTest extends FunctionalTestCase
{
  public function testListsAssignedTagsInNameOrder(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);

    $banana = $this->fixtures->createTag($user['id'], 'banana');
    $apple = $this->fixtures->createTag($user['id'], 'apple');
    $this->fixtures->createTag($user['id'], 'cherry');  // owned but NOT assigned to this place
    $this->fixtures->assignTag($placeId, $banana);
    $this->fixtures->assignTag($placeId, $apple);

    $response = $this->request('GET', "/api/places/{$placeId->toRfc4122()}/tags", null, $this->authHeader($user['id']));

    self::assertSame(200, $response->getStatusCode());
    self::assertSame(['apple', 'banana'], array_column($this->jsonBody($response), 'name'));
  }

  public function testReturnsEmptyArrayForPlaceWithNoTags(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);

    $response = $this->request('GET', "/api/places/{$placeId->toRfc4122()}/tags", null, $this->authHeader($user['id']));

    self::assertSame(200, $response->getStatusCode());
    self::assertSame([], $this->jsonBody($response));
  }

  public function testCannotListTagsForAnotherUsersPlace(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');
    $placeId = $this->fixtures->createPlace($owner['id']);
    $tagId = $this->fixtures->createTag($owner['id'], 'coffee');
    $this->fixtures->assignTag($placeId, $tagId);

    $response = $this->request('GET', "/api/places/{$placeId->toRfc4122()}/tags", null, $this->authHeader($attacker['id']));

    self::assertSame(404, $response->getStatusCode());
  }
}
