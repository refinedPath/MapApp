<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class TagUpdateTest extends FunctionalTestCase
{
  public function testUpdatesOwnTag(): void
  {
    $user = $this->fixtures->createUser();
    $tagId = $this->fixtures->createTag($user['id'], 'coffee', '#8a2be2', null);

    $response = $this->request('PUT', "/api/tags/{$tagId->toRfc4122()}", [
      'name' => 'espresso',
      'color' => '#123456',
      'emoji' => '☕',
    ], $this->authHeader($user['id']));

    self::assertSame(200, $response->getStatusCode());

    $body = $this->jsonBody($response);
    self::assertSame($tagId->toRfc4122(), $body['id']);
    self::assertSame('espresso', $body['name']);
    self::assertSame('#123456', $body['color']);
    self::assertSame('☕', $body['emoji']);
    self::assertArrayHasKey('created_at', $body);
    self::assertArrayHasKey('updated_at', $body);
  }

  public function testNoOpRenameToOwnNameSucceeds(): void
  {
    $user = $this->fixtures->createUser();
    $tagId = $this->fixtures->createTag($user['id'], 'coffee', '#8a2be2', null);

    $response = $this->request('PUT', "/api/tags/{$tagId->toRfc4122()}", [
      'name' => 'coffee',
      'color' => '#ffffff',
    ], $this->authHeader($user['id']));

    self::assertSame(200, $response->getStatusCode());
    self::assertSame('#ffffff', $this->jsonBody($response)['color']);
  }

  public function testRejectsRenameOntoAnotherOwnedTagName(): void
  {
    $user = $this->fixtures->createUser();
    $this->fixtures->createTag($user['id'], 'coffee');
    $teaId = $this->fixtures->createTag($user['id'], 'tea');

    $response = $this->request('PUT', "/api/tags/{$teaId->toRfc4122()}", [
      'name' => 'coffee',
    ], $this->authHeader($user['id']));

    self::assertSame(409, $response->getStatusCode());
  }

  public function testCannotUpdateAnotherUsersTag(): void
  {
    $owner = $this->fixtures->createUser('owner@mapapp.test');
    $attacker = $this->fixtures->createUser('attacker@mapapp.test');
    $tagId = $this->fixtures->createTag($owner['id'], 'coffee');

    $response = $this->request('PUT', "/api/tags/{$tagId->toRfc4122()}", [
      'name' => 'hacked',
    ], $this->authHeader($attacker['id']));

    self::assertSame(404, $response->getStatusCode());
  }

  public function testUpdateRejectsEmptyName(): void
  {
    $user = $this->fixtures->createUser();
    $tagId = $this->fixtures->createTag($user['id'], 'coffee');

    $response = $this->request('PUT', "/api/tags/{$tagId->toRfc4122()}", [
      'name' => '',
    ], $this->authHeader($user['id']));

    self::assertSame(422, $response->getStatusCode());
    self::assertArrayHasKey('name', $this->jsonBody($response)['errors']);
  }
}
