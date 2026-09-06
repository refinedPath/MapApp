<?php

declare(strict_types=1);

namespace App\Mail;

use Override;

final class InMemoryMailer implements MailerInterface
{
  /** @var list<Email> */
  private array $sent = [];

  #[Override]
  public function send(Email $email): void
  {
    $this->sent[] = $email;
  }

  /** @return list<Email> */
  public function sent(): array
  {
    return $this->sent;
  }

  public function lastTo(string $to): ?Email
  {
    foreach (array_reverse($this->sent) as $email) {
      if ($email->to === $to) {
        return $email;
      }
    }

    return null;
  }
}
