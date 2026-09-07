<?php

declare(strict_types=1);

namespace App\Validation;

final class PasswordPolicy
{
  public function __construct(
    public int $minLength,
    public bool $requireUppercase,
    public bool $requireLowercase,
    public bool $requireNumber,
    public bool $requireSymbol,
  ) {
  }

  /** @return list<string> */
  public function validate(string $password): array
  {
    $errors = [];

    if (mb_strlen($password) < $this->minLength) {
      $errors[] = "Password must be at least {$this->minLength} characters.";
    }
    if ($this->requireUppercase && preg_match('/\p{Lu}/u', $password) !== 1) {
      $errors[] = 'Password must contain an uppercase letter.';
    }
    if ($this->requireLowercase && preg_match('/\p{Ll}/u', $password) !== 1) {
      $errors[] = 'Password must contain a lowercase letter.';
    }
    if ($this->requireNumber && preg_match('/\p{N}/u', $password) !== 1) {
      $errors[] = 'Password must contain a number.';
    }
    if ($this->requireSymbol && preg_match('/[^\p{L}\p{N}]/u', $password) !== 1) {
      $errors[] = 'Password must contain a symbol.';
    }

    return $errors;
  }

  /**
   * @return array{min_length: int, require_uppercase: bool, require_lowercase: bool, require_number: bool, require_symbol: bool}
   */
  public function toArray(): array
  {
    return [
      'min_length' => $this->minLength,
      'require_uppercase' => $this->requireUppercase,
      'require_lowercase' => $this->requireLowercase,
      'require_number' => $this->requireNumber,
      'require_symbol' => $this->requireSymbol,
    ];
  }
}
