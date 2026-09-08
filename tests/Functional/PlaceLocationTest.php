<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class PlaceLocationTest extends FunctionalTestCase
{
  public function testUpdatesLocationOfOwnPlace(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id'], 'Original', 1.0, 2.0);

    $response = $this->request(
      'PUT',
      "/api/places/{$placeId->toRfc4122()}/location",
      ['latitude' => 51.5, 'longitude' => -0.12],
      $this->authHeader($user['id']),
    );

    self::assertSame(200, $response->getStatusCode());

    $body = $this->jsonBody($response);
    self::assertSame($placeId->toRfc4122(), $body['id']);
    self::assertSame(51.5, $body['latitude']);
    self::assertSame(-0.12, $body['longitude']);
    self::assertSame('Original', $body['name']);  // name/description untouched by a location PUT

    $lat = $this->pdo->prepare('SELECT ST_Y(location::geometry) FROM places WHERE id = :id');
    $lat->execute(['id' => $placeId->toRfc4122()]);
    self::assertEqualsWithDelta(51.5, (float) $lat->fetchColumn(), 0.0000001);

    $lng = $this->pdo->prepare('SELECT ST_X(location::geometry) FROM places WHERE id = :id');
    $lng->execute(['id' => $placeId->toRfc4122()]);
    self::assertEqualsWithDelta(-0.12, (float) $lng->fetchColumn(), 0.0000001);
  }

  public function testRejectsNonNumericCoordinates(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);

    $response = $this->request(
      'PUT',
      "/api/places/{$placeId->toRfc4122()}/location",
      ['latitude' => 'not-a-number', 'longitude' => 'also-not'],
      $this->authHeader($user['id']),
    );

    self::assertSame(422, $response->getStatusCode());
    $errors = $this->jsonBody($response)['errors'];
    self::assertArrayHasKey('latitude', $errors);
    self::assertArrayHasKey('longitude', $errors);
  }

  public function testRejectsOutOfRangeCoordinates(): void
  {
    $user = $this->fixtures->createUser();
    $placeId = $this->fixtures->createPlace($user['id']);

    $response = $this->request(
      'PUT',
      "/api/places/{$placeId->toRfc4122()}/location",
      ['latitude' => 91.0, 'longitude' => 0.0],
      $this->authHeader($user['id']),
    );

    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('location', $this->jsonBody($response)['errors']);
  }

  public function testCannotUpdateLocationOfAnotherUsersPlace(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');
    $placeId = $this->fixtures->createPlace($owner['id'], 'Owner Place', 1.0, 2.0);

    $response = $this->request(
      'PUT',
      "/api/places/{$placeId->toRfc4122()}/location",
      ['latitude' => 51.5, 'longitude' => -0.12],
      $this->authHeader($attacker['id']),
    );

    self::assertSame(404, $response->getStatusCode());

    // The owner's location is untouched — validation passed, but ownership blocked the write.
    $lat = $this->pdo->prepare('SELECT ST_Y(location::geometry) FROM places WHERE id = :id');
    $lat->execute(['id' => $placeId->toRfc4122()]);
    self::assertEqualsWithDelta(1.0, (float) $lat->fetchColumn(), 0.0000001);
  }

  public function testUnauthenticatedRequestReturns401(): void
  {
    $response = $this->request('PUT', '/api/places/' . \Symfony\Component\Uid\Uuid::v7()->toRfc4122() . '/location', [
      'latitude' => 51.5,
      'longitude' => -0.12,
    ]);

    self::assertSame(401, $response->getStatusCode());
  }
}
