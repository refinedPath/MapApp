<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Service\TokenService;

final class LoginTest extends FunctionalTestCase
{
  public function testValidCredentialsReturnAUsableToken(): void
  {
    $user = $this->fixtures->createUser('user@mapapp.test', 'correct-password');

    $response = $this->request('POST', '/api/login', [
      'email' => 'user@mapapp.test',
      'password' => 'correct-password',
    ]);

    self::assertSame(200, $response->getStatusCode());

    $body = $this->jsonBody($response);
    self::assertArrayHasKey('token', $body);
    self::assertIsString($body['token']);

    // The issued token verifies back to this exact user.
    /** @var TokenService $tokens */
    $tokens = $this->app->getContainer()->get(TokenService::class);
    self::assertTrue($user['id']->equals($tokens->verifyToken($body['token'])));
  }

  public function testWrongPasswordReturns401(): void
  {
    $this->fixtures->createUser('user@mapapp.test', 'correct-password');

    $response = $this->request('POST', '/api/login', [
      'email' => 'user@mapapp.test',
      'password' => 'wrong-password',
    ]);

    self::assertSame(401, $response->getStatusCode());
    self::assertSame('Invalid credentials.', $this->jsonBody($response)['error']);
  }

  public function testUnknownEmailReturns401WithTheSameMessage(): void
  {
    // No user created. Exercises the dummy-hash path; message must match the
    // wrong-password case exactly (no enumeration via message).
    $response = $this->request('POST', '/api/login', [
      'email' => 'nobody@mapapp.test',
      'password' => 'wrong-password',
    ]);

    self::assertSame(401, $response->getStatusCode());
    self::assertSame('Invalid credentials.', $this->jsonBody($response)['error']);
  }

  public function testMissingFieldsReturn422WithFieldErrors(): void
  {
    $response = $this->request('POST', '/api/login', [
      'email' => 'not-an-email',
      // password omitted
    ]);

    self::assertSame(422, $response->getStatusCode());

    $errors = $this->jsonBody($response)['errors'];
    self::assertArrayHasKey('email', $errors);     // invalid format
    self::assertArrayHasKey('password', $errors);  // required
  }
}
