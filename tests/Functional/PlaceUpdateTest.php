<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class PlaceUpdateTest extends FunctionalTestCase
{
  public function testUpdatesOwnPlaceNameAndDescription(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id'], 'Original Name', 12.5, 34.25);

    $response = $this->request('PUT', "/api/places/{$placeId->toRfc4122()}", [
      'name' => 'Updated Name',
      'description' => 'Updated description.',
    ], $this->authHeader($user['id']));

    self::assertSame(200, $response->getStatusCode());

    $body = $this->jsonBody($response);
    self::assertSame($placeId->toRfc4122(), $body['id']);
    self::assertSame('Updated Name', $body['name']);
    self::assertSame('Updated description.', $body['description']);
    self::assertEqualsWithDelta(12.5, $body['latitude'], 0.0000001);
    self::assertEqualsWithDelta(34.25, $body['longitude'], 0.0000001);
  }

  public function testUpdateRejectsEmptyName(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);

    $response = $this->request('PUT', "/api/places/{$placeId->toRfc4122()}", [
      'name' => '',
    ], $this->authHeader($user['id']));

    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('name', $this->jsonBody($response)['errors']);
  }

  public function testUpdateRejectsNameOverMaxLength(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);

    $response = $this->request('PUT', "/api/places/{$placeId->toRfc4122()}", [
      'name' => str_repeat('a', 256),  // MAX_NAME_LENGTH is 255
    ], $this->authHeader($user['id']));

    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('name', $this->jsonBody($response)['errors']);
  }

  public function testCannotUpdateAnotherUsersPlace(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');
    $placeId = $this->fixtures->createPlace($owner['id'], 'Owner Place');

    $response = $this->request('PUT', "/api/places/{$placeId->toRfc4122()}", [
      'name' => 'Hacked',
    ], $this->authHeader($attacker['id']));

    self::assertSame(404, $response->getStatusCode());
  }
}
