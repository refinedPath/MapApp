<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class PlaceCreateTest extends FunctionalTestCase
{
  public function testCreatesPlaceForAuthenticatedUser(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('POST', '/api/places', [
      'name' => 'Test Place',
      'description' => 'A place to test.',
      'latitude' => 40.5,
      'longitude' => -74.25,
    ], $this->authHeader($user['id']));

    self::assertSame(201, $response->getStatusCode());

    $body = $this->jsonBody($response);
    self::assertArrayHasKey('id', $body);
    self::assertSame('Test Place', $body['name']);
    self::assertSame('A place to test.', $body['description']);
    self::assertSame(40.5, $body['latitude']);
    self::assertSame(-74.25, $body['longitude']);
    self::assertArrayHasKey('created_at', $body);
    self::assertArrayHasKey('updated_at', $body);
  }

  public function testCreatesPlaceWithoutDescription(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('POST', '/api/places', [
      'name' => 'No Description',
      'latitude' => 1.0,
      'longitude' => 2.0,
    ], $this->authHeader($user['id']));

    self::assertSame(201, $response->getStatusCode());
    self::assertNull($this->jsonBody($response)['description']);
  }

  public function testRejectsEmptyName(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('POST', '/api/places', [
      'name' => '',
      'latitude' => 40.5,
      'longitude' => -74.25,
    ], $this->authHeader($user['id']));

    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('name', $this->jsonBody($response)['errors']);
  }

  public function testRejectsNameOverMaxLength(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('POST', '/api/places', [
      'name' => str_repeat('a', 256),  // MAX_NAME_LENGTH is 255
      'latitude' => 40.5,
      'longitude' => -74.25,
    ], $this->authHeader($user['id']));

    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('name', $this->jsonBody($response)['errors']);
  }

  public function testRejectsNonNumericCoordinates(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('POST', '/api/places', [
      'name' => 'Bad Coords',
      'latitude' => 'not-a-number',
      'longitude' => 'also-not',
    ], $this->authHeader($user['id']));

    self::assertSame(422, $response->getStatusCode());
    $errors = $this->jsonBody($response)['errors'];
    self::assertArrayHasKey('latitude', $errors);
    self::assertArrayHasKey('longitude', $errors);
  }

  public function testRejectsOutOfRangeCoordinates(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('POST', '/api/places', [
      'name' => 'Out Of Range',
      'latitude' => 91.0,   // numeric, but beyond +/-90
      'longitude' => 0.0,
    ], $this->authHeader($user['id']));

    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('location', $this->jsonBody($response)['errors']);
  }

  public function testUnauthenticatedRequestReturns401(): void
  {
    $response = $this->request('POST', '/api/places', [
      'name' => 'Test Place',
      'latitude' => 40.5,
      'longitude' => -74.25,
    ]);

    self::assertSame(401, $response->getStatusCode());
  }
}
