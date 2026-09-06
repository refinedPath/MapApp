<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Component\Uid\Uuid;

final class PlaceFilterTest extends FunctionalTestCase
{
  /**
   * @return array{userId: Uuid, coffee: Uuid, museum: Uuid, park: Uuid}
   */
  private function seedFilterScenario(): array
  {
    $user = $this->fixtures->createUser();
    $coffee = $this->fixtures->createTag($user['id'], 'coffee');
    $museum = $this->fixtures->createTag($user['id'], 'museum');
    $park = $this->fixtures->createTag($user['id'], 'park');

    // both coffee AND museum
    $both = $this->fixtures->createPlace($user['id'], 'Both');
    $this->fixtures->assignTag($both, $coffee);
    $this->fixtures->assignTag($both, $museum);

    // coffee only
    $coffeeOnly = $this->fixtures->createPlace($user['id'], 'CoffeeOnly');
    $this->fixtures->assignTag($coffeeOnly, $coffee);

    // park only (matches neither coffee nor museum)
    $parkOnly = $this->fixtures->createPlace($user['id'], 'ParkOnly');
    $this->fixtures->assignTag($parkOnly, $park);

    return ['userId' => $user['id'], 'coffee' => $coffee, 'museum' => $museum, 'park' => $park];
  }

  /** @return list<string> */
  private function names(\Psr\Http\Message\ResponseInterface $response): array
  {
    $names = [];
    foreach ($this->jsonBody($response) as $place) {
      $names[] = $place['name'];
    }
    sort($names);

    return $names;
  }

  public function testMatchAnyReturnsPlacesWithEitherTag(): void
  {
    $s = $this->seedFilterScenario();

    $response = $this->request(
      'GET',
      "/api/places?tags={$s['coffee']->toRfc4122()},{$s['museum']->toRfc4122()}&match=any",
      null,
      $this->authHeader($s['userId']),
    );

    self::assertSame(200, $response->getStatusCode());
    self::assertSame(['Both', 'CoffeeOnly'], $this->names($response));
  }

  public function testMatchAllReturnsOnlyPlacesWithEveryTag(): void
  {
    $s = $this->seedFilterScenario();

    $response = $this->request(
      'GET',
      "/api/places?tags={$s['coffee']->toRfc4122()},{$s['museum']->toRfc4122()}&match=all",
      null,
      $this->authHeader($s['userId']),
    );

    self::assertSame(200, $response->getStatusCode());
    self::assertSame(['Both'], $this->names($response));  // the DISTINCT/HAVING path
  }

  public function testNoTagsParamReturnsAllPlaces(): void
  {
    $s = $this->seedFilterScenario();

    $response = $this->request('GET', '/api/places', null, $this->authHeader($s['userId']));

    self::assertSame(200, $response->getStatusCode());
    self::assertSame(['Both', 'CoffeeOnly', 'ParkOnly'], $this->names($response));
  }

  public function testMalformedTagIdReturns422(): void
  {
    $s = $this->seedFilterScenario();

    $response = $this->request('GET', '/api/places?tags=not-a-uuid', null, $this->authHeader($s['userId']));

    self::assertSame(422, $response->getStatusCode());
  }

  public function testBadMatchModeReturns422(): void
  {
    $s = $this->seedFilterScenario();

    $response = $this->request(
      'GET',
      "/api/places?tags={$s['coffee']->toRfc4122()}&match=xor",
      null,
      $this->authHeader($s['userId']),
    );

    self::assertSame(422, $response->getStatusCode());
  }
}
