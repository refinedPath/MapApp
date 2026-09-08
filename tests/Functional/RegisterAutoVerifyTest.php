<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Mail\InMemoryMailer;
use App\Mail\MailerInterface;
use App\Repository\UserRepositoryInterface;

final class RegisterAutoVerifyTest extends FunctionalTestCase
{
  private const STRONG_PASSWORD = 'Str0ng-Passw0rd!';

  protected function setUp(): void
  {
    // The app bootstrap re-reads $_ENV and never reloads dotenv, so this override
    // reaches the fresh container that the parent setUp builds.
    $_ENV['AUTO_VERIFY_NEW_ACCOUNTS'] = 'true';
    parent::setUp();
  }

  protected function tearDown(): void
  {
    parent::tearDown();
    unset($_ENV['AUTO_VERIFY_NEW_ACCOUNTS']);  // don't leak the flag to other tests
  }

  public function testNewAccountIsVerifiedImmediatelyAndSendsNoEmail(): void
  {
    $response = $this->request('POST', '/api/register', [
      'email' => 'auto@mapapp.test',
      'password' => self::STRONG_PASSWORD,
    ]);

    self::assertSame(202, $response->getStatusCode());

    /** @var UserRepositoryInterface $users */
    $users = $this->app->getContainer()->get(UserRepositoryInterface::class);
    $user = $users->findByEmail('auto@mapapp.test');
    self::assertNotNull($user);
    self::assertNotNull($user->emailVerifiedAt);  // verified at creation, no link needed

    $mailer = $this->app->getContainer()->get(MailerInterface::class);
    self::assertInstanceOf(InMemoryMailer::class, $mailer);
    self::assertCount(0, $mailer->sent());  // no verification email in auto-verify mode
  }

  public function testAutoVerifiedUserCanLogInWithoutVerifying(): void
  {
    $this->request('POST', '/api/register', [
      'email' => 'auto-login@mapapp.test',
      'password' => self::STRONG_PASSWORD,
    ]);

    $login = $this->request('POST', '/api/login', [
      'email' => 'auto-login@mapapp.test',
      'password' => self::STRONG_PASSWORD,
    ]);

    self::assertSame(200, $login->getStatusCode());  // not 403, login's gate passes because the account is verified
    self::assertArrayHasKey('token', $this->jsonBody($login));
  }
}
