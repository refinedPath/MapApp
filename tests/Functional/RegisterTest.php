<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Mail\InMemoryMailer;
use App\Mail\MailerInterface;
use App\Repository\UserRepositoryInterface;

final class RegisterTest extends FunctionalTestCase
{
  private const STRONG_PASSWORD = 'Str0ng-Passw0rd!';

  private function mailer(): InMemoryMailer
  {
    $m = $this->app->getContainer()->get(MailerInterface::class);
    self::assertInstanceOf(InMemoryMailer::class, $m);

    return $m;
  }

  private function users(): UserRepositoryInterface
  {
    /** @var UserRepositoryInterface $r */
    $r = $this->app->getContainer()->get(UserRepositoryInterface::class);

    return $r;
  }

  public function testNewEmailReturns202CreatesUnverifiedUserAndSendsVerification(): void
  {
    $response = $this->request('POST', '/api/register', [
      'email' => 'new@mapapp.test',
      'password' => self::STRONG_PASSWORD,
    ]);

    self::assertSame(202, $response->getStatusCode());
    self::assertSame('Check your email to verify your account.', $this->jsonBody($response)['message']);
    self::assertArrayNotHasKey('token', $this->jsonBody($response));  // no auto-login

    $user = $this->users()->findByEmail('new@mapapp.test');
    self::assertNotNull($user);
    self::assertNull($user->emailVerifiedAt);  // unverified until the link is used

    $sent = $this->mailer()->sent();
    self::assertCount(1, $sent);
    self::assertSame('new@mapapp.test', $sent[0]->to);
    self::assertMatchesRegularExpression('/\?verify=[a-f0-9]{64}/', $sent[0]->body);
  }

  public function testExistingEmailGivesIdenticalResponseWithoutDuplicatingOrEmailing(): void
  {
    $existing = $this->fixtures->createUser('taken@mapapp.test');  // verified

    $response = $this->request('POST', '/api/register', [
      'email' => 'taken@mapapp.test',
      'password' => self::STRONG_PASSWORD,
    ]);

    // Identical to the new-email path — no enumeration.
    self::assertSame(202, $response->getStatusCode());
    self::assertSame('Check your email to verify your account.', $this->jsonBody($response)['message']);

    self::assertCount(0, $this->mailer()->sent());  // no verification email for a taken address

    $user = $this->users()->findByEmail('taken@mapapp.test');
    self::assertNotNull($user);
    self::assertTrue($existing['id']->equals($user->id));  // not duplicated, not overwritten
  }

  public function testEmptyEmailReturns422(): void
  {
    $response = $this->request('POST', '/api/register', ['email' => '', 'password' => 'password123']);
    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('email', $this->jsonBody($response)['errors']);
  }

  public function testInvalidEmailReturns422(): void
  {
    $response = $this->request('POST', '/api/register', ['email' => 'not-an-email', 'password' => 'password123']);
    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('email', $this->jsonBody($response)['errors']);
  }

  public function testEmptyPasswordReturns422(): void
  {
    $response = $this->request('POST', '/api/register', ['email' => 'x@mapapp.test', 'password' => '']);
    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('password', $this->jsonBody($response)['errors']);
  }

  public function testShortPasswordReturns422(): void
  {
    $response = $this->request('POST', '/api/register', ['email' => 'x@mapapp.test', 'password' => 'short']);
    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('password', $this->jsonBody($response)['errors']);
  }
}
