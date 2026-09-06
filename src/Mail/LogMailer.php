<?php

declare(strict_types=1);

namespace App\Mail;

use Override;
use Psr\Log\LoggerInterface as LogLoggerInterface;

final class LogMailer implements MailerInterface
{
  public function __construct(
    private readonly ?LogLoggerInterface $logger = null,
  ) {
  }

  #[Override]
  public function send(Email $email): void
  {
    $line = sprintf(
      "[MAIL] to=%s subject=%s\n%s\n",
      $email->to,
      $email->subject,
      $email->body,
    );

    if ($this->logger instanceof LogLoggerInterface) {
      $this->logger->info($line);
    } else {
      error_log($line);
    }
  }
}
