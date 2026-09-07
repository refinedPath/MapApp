<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Mail\InMemoryMailer;
use App\Mail\MailerInterface;
use App\Repository\EmailVerificationTokenRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class VerifyEmailTest extends FunctionalTestCase
{
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

  private function tokens(): EmailVerificationTokenRepositoryInterface
  {
    /** @var EmailVerificationTokenRepositoryInterface $r */
    $r = $this->app->getContainer()->get(EmailVerificationTokenRepositoryInterface::class);

    return $r;
  }

  /** Register a fresh user and pull the raw token out of the sent email. */
  private function registerAndGetToken(string $email): string
  {
    $this->request('POST', '/api/register', ['email' => $email, 'password' => 'Str0ng-Passw0rd!']);
    preg_match('/\?verify=([a-f0-9]{64})/', $this->mailer()->sent()[0]->body, $m);

    return $m[1];
  }

  public function testValidTokenVerifiesTheAccount(): void
  {
    $raw = $this->registerAndGetToken('verify@mapapp.test');

    $response = $this->request('POST', '/api/verify-email', ['token' => $raw]);

    self::assertSame(200, $response->getStatusCode());
    self::assertSame('Email verified. You can now log in.', $this->jsonBody($response)['message']);

    $user = $this->users()->findByEmail('verify@mapapp.test');
    self::assertNotNull($user);
    self::assertNotNull($user->emailVerifiedAt);                        // verified
    self::assertNull($this->tokens()->findByHash(hash('sha256', $raw))); // consumed
  }

  public function testUnknownTokenReturns400(): void
  {
    $response = $this->request('POST', '/api/verify-email', ['token' => bin2hex(random_bytes(32))]);

    self::assertSame(400, $response->getStatusCode());
  }

  public function testExpiredTokenReturns410(): void
  {
    $u = $this->fixtures->createUser('exp@mapapp.test', verified: false);
    $raw = bin2hex(random_bytes(32));
    $this->tokens()->create($u['id'], hash('sha256', $raw), (new \DateTimeImmutable())->sub(new \DateInterval('PT1S')));

    $response = $this->request('POST', '/api/verify-email', ['token' => $raw]);

    self::assertSame(410, $response->getStatusCode());
  }

  public function testMissingTokenReturns422(): void
  {
    $response = $this->request('POST', '/api/verify-email', []);

    self::assertSame(422, $response->getStatusCode());
  }

  public function testTokenIsSingleUse(): void
  {
    $raw = $this->registerAndGetToken('once@mapapp.test');

    $first = $this->request('POST', '/api/verify-email', ['token' => $raw]);
    self::assertSame(200, $first->getStatusCode());

    // Second use: token was consumed → now invalid.
    $second = $this->request('POST', '/api/verify-email', ['token' => $raw]);
    self::assertSame(400, $second->getStatusCode());
  }
}
