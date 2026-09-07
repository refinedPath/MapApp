<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Mail\InMemoryMailer;
use App\Mail\MailerInterface;

final class ResendVerificationTest extends FunctionalTestCase
{
  private function mailer(): InMemoryMailer
  {
    $m = $this->app->getContainer()->get(MailerInterface::class);
    self::assertInstanceOf(InMemoryMailer::class, $m);

    return $m;
  }

  public function testResendForUnverifiedUserSendsAnEmail(): void
  {
    // Unverified account with no live token (past any cooldown).
    $this->fixtures->createUser('unv@mapapp.test', verified: false);

    $response = $this->request('POST', '/api/resend-verification', ['email' => 'unv@mapapp.test']);

    self::assertSame(202, $response->getStatusCode());
    self::assertCount(1, $this->mailer()->sent());
    self::assertSame('unv@mapapp.test', $this->mailer()->sent()[0]->to);
  }

  public function testResendForVerifiedUserSendsNothingButSameResponse(): void
  {
    $this->fixtures->createUser('done@mapapp.test');  // verified

    $response = $this->request('POST', '/api/resend-verification', ['email' => 'done@mapapp.test']);

    self::assertSame(202, $response->getStatusCode());
    self::assertCount(0, $this->mailer()->sent());  // already verified → no email
  }

  public function testResendForUnknownEmailSendsNothingButSameResponse(): void
  {
    $response = $this->request('POST', '/api/resend-verification', ['email' => 'nobody@mapapp.test']);

    self::assertSame(202, $response->getStatusCode());
    self::assertCount(0, $this->mailer()->sent());
  }

  public function testResendWithInvalidEmailReturns422(): void
  {
    $response = $this->request('POST', '/api/resend-verification', ['email' => 'not-an-email']);

    self::assertSame(422, $response->getStatusCode());
  }

  public function testAllThreeAccountStatesGiveByteIdenticalBodies(): void
  {
    $this->fixtures->createUser('u1@mapapp.test', verified: false);
    $this->fixtures->createUser('u2@mapapp.test');  // verified

    $unverified = $this->jsonBody($this->request('POST', '/api/resend-verification', ['email' => 'u1@mapapp.test']));
    $verified = $this->jsonBody($this->request('POST', '/api/resend-verification', ['email' => 'u2@mapapp.test']));
    $unknown = $this->jsonBody($this->request('POST', '/api/resend-verification', ['email' => 'nobody@mapapp.test']));

    // Anti-enumeration. Identical bodies across all states.
    self::assertSame($unverified, $verified);
    self::assertSame($verified, $unknown);
  }
}
