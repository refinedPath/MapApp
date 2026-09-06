<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class UuidParamMiddlewareTest extends FunctionalTestCase
{
  public function testMalformedPlaceIdReturns404(): void
  {
    $user = $this->fixtures->createUser();

    // 'not-a-uuid'
    $response = $this->request('GET', '/api/places/not-a-uuid', null, $this->authHeader($user['id']));

    self::assertSame(404, $response->getStatusCode());
    self::assertSame('Not found.', $this->jsonBody($response)['error']);
  }

  public function testWellFormedButMissingPlaceIdReturns404(): void
  {
    $user = $this->fixtures->createUser();

    // Valid UUID shape, but no such place
    $missing = \Symfony\Component\Uid\Uuid::v7()->toRfc4122();
    $response = $this->request('GET', "/api/places/{$missing}", null, $this->authHeader($user['id']));

    self::assertSame(404, $response->getStatusCode());
  }
}
