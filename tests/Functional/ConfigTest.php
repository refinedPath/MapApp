<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class ConfigTest extends FunctionalTestCase
{
  public function testConfigExposesTagDefaultsAndPasswordPolicy(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('GET', '/api/config', null, $this->authHeader($user['id']));

    self::assertSame(200, $response->getStatusCode());
    $body = $this->jsonBody($response);

    self::assertArrayHasKey('tag', $body);

    self::assertSame(12, $body['password']['min_length']);
    self::assertTrue($body['password']['require_uppercase']);
    self::assertTrue($body['password']['require_lowercase']);
    self::assertTrue($body['password']['require_number']);
    self::assertTrue($body['password']['require_symbol']);
  }

  public function testConfigRequiresAuth(): void
  {
    $response = $this->request('GET', '/api/config');

    self::assertSame(401, $response->getStatusCode());
  }
}
