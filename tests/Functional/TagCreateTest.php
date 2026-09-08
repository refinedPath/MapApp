<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Tag;

final class TagCreateTest extends FunctionalTestCase
{
  public function testCreatesTagForAuthenticatedUser(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('POST', '/api/tags', [
      'name' => 'coffee',
      'color' => '#8a2be2',
      'emoji' => '☕',
    ], $this->authHeader($user['id']));

    self::assertSame(201, $response->getStatusCode());

    $body = $this->jsonBody($response);
    self::assertArrayHasKey('id', $body);
    self::assertSame('coffee', $body['name']);
    self::assertSame('#8a2be2', $body['color']);
    self::assertSame('☕', $body['emoji']);
    self::assertArrayHasKey('created_at', $body);
    self::assertArrayHasKey('updated_at', $body);
  }

  public function testCreatesTagWithDefaultColorWhenOmitted(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('POST', '/api/tags', [
      'name' => 'no-color',
    ], $this->authHeader($user['id']));

    self::assertSame(201, $response->getStatusCode());
    $body = $this->jsonBody($response);
    // The server owns the canonical default; color is optional in the API.
    self::assertSame(Tag::DEFAULT_COLOR, $body['color']);
    self::assertNull($body['emoji']);  // absent → null
  }

  public function testRejectsDuplicateNameForSameUser(): void
  {
    $user = $this->fixtures->createUser();
    $this->fixtures->createTag($user['id'], 'coffee');

    $response = $this->request('POST', '/api/tags', [
      'name' => 'coffee',
    ], $this->authHeader($user['id']));

    // 23505 on UNIQUE(user_id, name)
    self::assertSame(409, $response->getStatusCode());
  }

  public function testDuplicateCheckIsCaseInsensitive(): void
  {
    $user = $this->fixtures->createUser();
    $this->fixtures->createTag($user['id'], 'coffee');

    $response = $this->request('POST', '/api/tags', [
      'name' => 'COFFEE',  // citext column → collides with 'coffee'
    ], $this->authHeader($user['id']));

    self::assertSame(409, $response->getStatusCode());
  }

  public function testAllowsSameNameForDifferentUsers(): void
  {
    $alice = $this->fixtures->createUser('alice@mapapp.test');
    $bob = $this->fixtures->createUser('bob@mapapp.test');
    $this->fixtures->createTag($alice['id'], 'coffee');

    $response = $this->request('POST', '/api/tags', [
      'name' => 'coffee',
    ], $this->authHeader($bob['id']));

    self::assertSame(201, $response->getStatusCode());
  }

  public function testRejectsEmptyName(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('POST', '/api/tags', [
      'name' => '',
      'color' => '#8a2be2',
    ], $this->authHeader($user['id']));

    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('name', $this->jsonBody($response)['errors']);
  }

  public function testRejectsInvalidColor(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('POST', '/api/tags', [
      'name' => 'bad-color',
      'color' => 'not-a-hex',  // fails /^#[0-9a-fA-F]{6}$/
    ], $this->authHeader($user['id']));

    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('color', $this->jsonBody($response)['errors']);
  }

  public function testRejectsMultiGraphemeEmoji(): void
  {
    $user = $this->fixtures->createUser();

    $response = $this->request('POST', '/api/tags', [
      'name' => 'bad-emoji',
      'color' => '#8a2be2',
      'emoji' => '🎉🎊',  // two graphemes → grapheme_strlen !== 1
    ], $this->authHeader($user['id']));

    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('emoji', $this->jsonBody($response)['errors']);
  }
}
