<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class OwnershipIsolationTest extends FunctionalTestCase
{
  public function testUserCannotReadAnotherUsersPlace(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');
    $placeId = $this->fixtures->createPlace($owner['id'], 'Owner Place');

    $response = $this->request('GET', "/api/places/{$placeId->toRfc4122()}", null, $this->authHeader($attacker['id']));

    // 404, not 403 — never confirm the id exists to a non-owner.
    self::assertSame(404, $response->getStatusCode());
  }

  public function testUserCannotDeleteAnotherUsersPlace(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');
    $placeId = $this->fixtures->createPlace($owner['id'], 'Owner Place');

    $del = $this->request('DELETE', "/api/places/{$placeId->toRfc4122()}", null, $this->authHeader($attacker['id']));
    self::assertSame(404, $del->getStatusCode());

    // And the place still exists for its real owner.
    $stillThere = $this->request('GET', "/api/places/{$placeId->toRfc4122()}", null, $this->authHeader($owner['id']));
    self::assertSame(200, $stillThere->getStatusCode());
  }

  public function testFilterNeverReturnsAnotherUsersPlaces(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');

    $ownerCoffee = $this->fixtures->createTag($owner['id'], 'coffee');
    $ownerPlace = $this->fixtures->createPlace($owner['id'], 'Owner Coffee');
    $this->fixtures->assignTag($ownerPlace, $ownerCoffee);

    $attackerCoffee = $this->fixtures->createTag($attacker['id'], 'coffee');

    // Attacker filters by their own tag id — must see none of the owner's places.
    $response = $this->request(
      'GET',
      "/api/places?tags={$attackerCoffee->toRfc4122()}&match=any",
      null,
      $this->authHeader($attacker['id']),
    );
    self::assertSame(200, $response->getStatusCode());
    self::assertSame([], $this->jsonBody($response));

    // Even passing the OWNER's tag id can't widen past the ownership scope.
    $response2 = $this->request(
      'GET',
      "/api/places?tags={$ownerCoffee->toRfc4122()}&match=any",
      null,
      $this->authHeader($attacker['id']),
    );
    self::assertSame(200, $response2->getStatusCode());
    self::assertSame([], $this->jsonBody($response2));
  }

  public function testUnauthenticatedRequestReturns401(): void
  {
    $this->fixtures->createUser();

    $response = $this->request('GET', '/api/places');  // no auth header

    self::assertSame(401, $response->getStatusCode());
  }
}
