<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Exception\ExpiredVerificationTokenException;
use App\Exception\InvalidVerificationTokenException;
use App\Mail\InMemoryMailer;
use App\Mail\MailerInterface;
use App\Repository\EmailVerificationTokenRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\EmailVerificationService;

final class EmailVerificationServiceTest extends FunctionalTestCase
{
  private function service(): EmailVerificationService
  {
    /** @var EmailVerificationService $s */
    $s = $this->app->getContainer()->get(EmailVerificationService::class);

    return $s;
  }

  private function mailer(): InMemoryMailer
  {
    $m = $this->app->getContainer()->get(MailerInterface::class);
    self::assertInstanceOf(InMemoryMailer::class, $m, 'test env must use MAIL_TRANSPORT=memory');

    return $m;
  }

  private function tokens(): EmailVerificationTokenRepositoryInterface
  {
    /** @var EmailVerificationTokenRepositoryInterface $r */
    $r = $this->app->getContainer()->get(EmailVerificationTokenRepositoryInterface::class);

    return $r;
  }

  private function users(): UserRepositoryInterface
  {
    /** @var UserRepositoryInterface $r */
    $r = $this->app->getContainer()->get(UserRepositoryInterface::class);

    return $r;
  }

  public function testSendVerificationStoresTokenAndEmailsALink(): void
  {
    $u = $this->fixtures->createUser('verify@mapapp.test');
    $user = $this->users()->findById($u['id']);
    self::assertNotNull($user);

    $this->service()->sendVerification($user);

    $sent = $this->mailer()->sent();
    self::assertCount(1, $sent);
    self::assertSame('verify@mapapp.test', $sent[0]->to);
    self::assertMatchesRegularExpression('/\?verify=[a-f0-9]{64}/', $sent[0]->body);

    // Still unverified until the link is used.
    $fresh = $this->users()->findById($u['id']);
    self::assertNotNull($fresh);
    self::assertNull($fresh->emailVerifiedAt);
  }

  public function testVerifyWithTheEmailedTokenMarksUserVerifiedAndConsumesToken(): void
  {
    $u = $this->fixtures->createUser();
    $user = $this->users()->findById($u['id']);
    self::assertNotNull($user);

    $this->service()->sendVerification($user);
    preg_match('/\?verify=([a-f0-9]{64})/', $this->mailer()->sent()[0]->body, $m);
    $raw = $m[1];

    $this->service()->verify($raw);

    $fresh = $this->users()->findById($u['id']);
    self::assertNotNull($fresh);
    self::assertNotNull($fresh->emailVerifiedAt);              // verified
    self::assertNull($this->tokens()->findByHash(hash('sha256', $raw)));  // single-use: consumed
  }

  public function testVerifyWithUnknownTokenThrowsInvalid(): void
  {
    $this->expectException(InvalidVerificationTokenException::class);
    $this->service()->verify(bin2hex(random_bytes(32)));
  }

  public function testVerifyWithExpiredTokenThrowsExpired(): void
  {
    $u = $this->fixtures->createUser();
    $raw = bin2hex(random_bytes(32));
    // Arrange an already-expired token directly (expiresAt is caller-controlled).
    $this->tokens()->create($u['id'], hash('sha256', $raw), (new \DateTimeImmutable())->sub(new \DateInterval('PT1S')));

    $this->expectException(ExpiredVerificationTokenException::class);
    $this->service()->verify($raw);
  }

  public function testResendWithinCooldownSendsOnlyOneEmail(): void
  {
    $u = $this->fixtures->createUser();
    $user = $this->users()->findById($u['id']);
    self::assertNotNull($user);

    $this->service()->sendVerification($user);
    $this->service()->sendVerification($user);  // within 60s → cooldown no-op

    self::assertCount(1, $this->mailer()->sent());
  }
}
