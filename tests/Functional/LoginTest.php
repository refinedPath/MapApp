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

  public function testUnverifiedUserCannotLogInAndGets403(): void
  {
    $this->fixtures->createUser('unverified@mapapp.test', 'correct-password', verified: false);

    $response = $this->request('POST', '/api/login', [
      'email' => 'unverified@mapapp.test',
      'password' => 'correct-password',
    ]);

    self::assertSame(403, $response->getStatusCode());
    self::assertArrayNotHasKey('token', $this->jsonBody($response));  // no token for unverified
  }

  public function testLoginRehashesAnOutdatedPasswordHash(): void
  {
    // Arrange a verified user whose stored hash is deliberately weaker than the
    // current default, so it needs rehash.
    $email = 'oldhash@mapapp.test';
    $password = 'correct-password';
    $weakHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);

    $id = \Symfony\Component\Uid\Uuid::v7();
    $this->pdo->prepare(
      'INSERT INTO users (id, email, password_hash, email_verified_at)
       VALUES (:id, :email, :hash, :verified)'
    )->execute([
      'id' => $id->toRfc4122(),
      'email' => $email,
      'hash' => $weakHash,
      'verified' => (new \DateTimeImmutable())->format('Y-m-d H:i:sP'),
    ]);

    self::assertTrue(password_needs_rehash($weakHash, PASSWORD_DEFAULT));  // precondition

    $response = $this->request('POST', '/api/login', ['email' => $email, 'password' => $password]);
    self::assertSame(200, $response->getStatusCode());

    // The stored hash was upgraded, still verifies the same password, no longer needs rehash.
    $user = $this->app->getContainer()->get(\App\Repository\UserRepositoryInterface::class)->findByEmail($email);
    self::assertNotNull($user);
    self::assertNotSame($weakHash, $user->passwordHash);                       // changed
    self::assertTrue(password_verify($password, $user->passwordHash));         // still correct
    self::assertFalse(password_needs_rehash($user->passwordHash, PASSWORD_DEFAULT));  // upgraded
  }
}
