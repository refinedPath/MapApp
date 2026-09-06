<?php

declare(strict_types=1);

namespace App\Mail;

final readonly class Email
{
  public function __construct(
    public string $to,
    public string $subject,
    public string $body,
  ) {
  }
}
