<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Validation\PasswordPolicy;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
  private function policy(
    int $minLength,
    bool $up = false,
    bool $low = false,
    bool $num = false,
    bool $sym = false,
  ): PasswordPolicy {
    return new PasswordPolicy($minLength, $up, $low, $num, $sym);
  }

  public function testAcceptsPasswordMeetingMinimumLength(): void
  {
    self::assertSame([], $this->policy(8)->validate('abcdefgh'));
  }

  public function testRejectsPasswordShorterThanMinimum(): void
  {
    self::assertContains('Password must be at least 8 characters.', $this->policy(8)->validate('short'));
  }

  public function testMinLengthIsConfigurable(): void
  {
    self::assertSame([], $this->policy(4)->validate('abcd'));
    self::assertNotEmpty($this->policy(12)->validate('abcdefgh'));
  }

  public function testUppercaseRuleOnlyEnforcedWhenEnabled(): void
  {
    self::assertSame([], $this->policy(4)->validate('abcd'));
    self::assertNotEmpty($this->policy(4, up: true)->validate('abcd'));
    self::assertSame([], $this->policy(4, up: true)->validate('Abcd'));
  }

  public function testNumberRuleOnlyEnforcedWhenEnabled(): void
  {
    self::assertNotEmpty($this->policy(4, num: true)->validate('abcd'));
    self::assertSame([], $this->policy(4, num: true)->validate('abc1'));
  }

  public function testSymbolRuleOnlyEnforcedWhenEnabled(): void
  {
    self::assertNotEmpty($this->policy(4, sym: true)->validate('abcd'));
    self::assertSame([], $this->policy(4, sym: true)->validate('abc!'));
  }

  public function testReturnsAllFailingRulesAtOnce(): void
  {
    $errors = $this->policy(8, num: true, sym: true)->validate('ab');

    self::assertCount(3, $errors);
    self::assertSame('Password must be at least 8 characters.', $errors[0]);
    self::assertContains('Password must contain a number.', $errors);
    self::assertContains('Password must contain a symbol.', $errors);
  }

  public function testStrictPolicyReportsEveryUnmetRule(): void
  {
    // 12 + all complexity true. 'abc' → fails length, uppercase, number, symbol.
    $errors = $this->policy(12, up: true, low: true, num: true, sym: true)->validate('abc');

    self::assertCount(4, $errors);
    self::assertContains('Password must be at least 12 characters.', $errors);
    self::assertContains('Password must contain an uppercase letter.', $errors);
    self::assertContains('Password must contain a number.', $errors);
    self::assertContains('Password must contain a symbol.', $errors);
  }

  public function testStrictPolicyAcceptsAStrongPassword(): void
  {
    self::assertSame([], $this->policy(12, up: true, low: true, num: true, sym: true)->validate('Str0ng-Passw0rd!'));
  }

  public function testToArrayExposesTheParameters(): void
  {
    self::assertSame([
      'min_length' => 10,
      'require_uppercase' => false,
      'require_lowercase' => false,
      'require_number' => true,
      'require_symbol' => false,
    ], $this->policy(10, num: true)->toArray());
  }
}
