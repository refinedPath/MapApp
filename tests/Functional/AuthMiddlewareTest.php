<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class AuthMiddlewareTest extends FunctionalTestCase
{
  public function testMissingAuthorizationHeaderReturns401(): void
  {
    $response = $this->request('GET', '/api/places');

    self::assertSame(401, $response->getStatusCode());
    self::assertSame('Unauthorized.', $this->jsonBody($response)['error']);
  }

  public function testNonBearerAuthorizationHeaderReturns401(): void
  {
    $response = $this->request('GET', '/api/places', null, ['Authorization' => 'Basic dXNlcjpwYXNz']);

    self::assertSame(401, $response->getStatusCode());
  }

  public function testGarbageBearerTokenReturns401(): void
  {
    $response = $this->request('GET', '/api/places', null, ['Authorization' => 'Bearer not-a-real-jwt']);

    self::assertSame(401, $response->getStatusCode());
  }

  public function testValidTokenReachesTheController(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('GET', '/api/places', null, $this->authHeader($user['id']));

    // Passes auth → controller runs → 200 (empty list for a fresh user).
    self::assertSame(200, $response->getStatusCode());
    self::assertSame([], $this->jsonBody($response));
  }
}
