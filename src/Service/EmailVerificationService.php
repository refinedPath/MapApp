<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Exception\ExpiredVerificationTokenException;
use App\Exception\InvalidVerificationTokenException;
use App\Mail\Email;
use App\Mail\MailerInterface;
use App\Repository\EmailVerificationTokenRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class EmailVerificationService
{
  private const RESEND_COOLDOWN_SECONDS = 60; // min interval between outbound sends

  public function __construct(
    private readonly EmailVerificationTokenRepositoryInterface $tokens,
    private readonly UserRepositoryInterface $users,
    private readonly MailerInterface $mailer,
    private readonly string $appUrl,
    private readonly int $ttlHours,
  ) {
  }

  /**
   * Mint a fresh verification token and email the link. Silently no-ops if a token
   * was already issued within the cooldown window — bounds outbound email under
   * enumeration/abuse
   */
  public function sendVerification(User $user): void
  {
    $now = new \DateTimeImmutable();

    $cooldownStart = $now->sub(new \DateInterval('PT' . self::RESEND_COOLDOWN_SECONDS . 'S'));
    if ($this->tokens->existsForUserSince($user->id, $cooldownStart)) {
      return;
    }

    // One active token per user: drop any prior before minting a new one.
    $this->tokens->deleteForUser($user->id);

    $rawToken = bin2hex(random_bytes(32));          // 256-bit, 64 hex chars — goes in the link
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = $now->add(new \DateInterval('PT' . $this->ttlHours . 'H'));

    $this->tokens->create($user->id, $tokenHash, $expiresAt);

    $link = rtrim($this->appUrl, '/') . '/?verify=' . $rawToken;

    $expiry = $this->ttlHours === 1 ? '1 hour' : "{$this->ttlHours} hours";
    $this->mailer->send(new Email(
      to: $user->email,
      subject: 'Verify your email address',
      body: "Welcome to MapApp!\n\nConfirm your email by opening this link:\n{$link}\n\nThe link expires in {$expiry}.",
    ));
  }

  /**
   * @throws InvalidVerificationTokenException  no such token
   * @throws ExpiredVerificationTokenException  token found but past expiry
   */
  public function verify(string $rawToken): void
  {
    $tokenHash = hash('sha256', $rawToken);
    $row = $this->tokens->findByHash($tokenHash);

    if ($row === null) {
      throw new InvalidVerificationTokenException('Verification token is invalid.');
    }

    if (new \DateTimeImmutable($row['expires_at']) <= new \DateTimeImmutable()) {
      throw new ExpiredVerificationTokenException('Verification token has expired.');
    }

    $userId = Uuid::fromString($row['user_id']);
    $this->users->markEmailVerified($userId, new \DateTimeImmutable());
    $this->tokens->deleteForUser($userId);
  }
}
