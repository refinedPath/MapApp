<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class ConfigTest extends FunctionalTestCase
{
  public function testPublicConfigReturnsPasswordPolicyWithoutAuth(): void
  {
    $response = $this->request('GET', '/api/config'); // no auth

    self::assertSame(200, $response->getStatusCode());
    $body = $this->jsonBody($response);

    self::assertSame(12, $body['password']['min_length']);
    self::assertTrue($body['password']['require_uppercase']);
    self::assertArrayNotHasKey('tag', $body); // public tier excludes per-user config
  }

  public function testMeConfigReturnsSupersetWhenAuthed(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('GET', '/api/config/me', null, $this->authHeader($user['id']));

    self::assertSame(200, $response->getStatusCode());
    $body = $this->jsonBody($response);

    self::assertSame(12, $body['password']['min_length']);  // includes everything public
    self::assertArrayHasKey('tag', $body);                  // plus the per-user extras
  }

  public function testMeConfigRequiresAuth(): void
  {
    $response = $this->request('GET', '/api/config/me');  // no auth

    self::assertSame(401, $response->getStatusCode());
  }
}
