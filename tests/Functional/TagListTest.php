<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class TagListTest extends FunctionalTestCase
{
  public function testListsOnlyOwnTagsInNameOrder(): void
  {
    $user = $this->fixtures->createUser();
    $other = $this->fixtures->createUser('other@mapapp.test');

    $this->fixtures->createTag($user['id'], 'banana');
    $this->fixtures->createTag($user['id'], 'apple');
    $this->fixtures->createTag($user['id'], 'cherry');
    $this->fixtures->createTag($other['id'], 'zebra');

    $response = $this->request('GET', '/api/tags', null, $this->authHeader($user['id']));

    self::assertSame(200, $response->getStatusCode());
    self::assertSame(['apple', 'banana', 'cherry'], array_column($this->jsonBody($response), 'name'));
  }

  public function testTagListIsEmptyForUserWithNoTags(): void
  {
    $user = $this->fixtures->createUser();
    $other = $this->fixtures->createUser('other@mapapp.test');
    $this->fixtures->createTag($other['id'], 'zebra');

    $response = $this->request('GET', '/api/tags', null, $this->authHeader($user['id']));

    self::assertSame(200, $response->getStatusCode());
    self::assertSame([], $this->jsonBody($response));
  }

  public function testCountsReflectAssignmentsAndAreOwnOnly(): void
  {
    $user = $this->fixtures->createUser();
    $other = $this->fixtures->createUser('other@mapapp.test');

    $coffee = $this->fixtures->createTag($user['id'], 'coffee');
    $this->fixtures->createTag($user['id'], 'unused');
    $p1 = $this->fixtures->createPlace($user['id'], 'P1');
    $p2 = $this->fixtures->createPlace($user['id'], 'P2');
    $this->fixtures->assignTag($p1, $coffee);
    $this->fixtures->assignTag($p2, $coffee);

    $otherTag = $this->fixtures->createTag($other['id'], 'coffee');
    $otherPlace = $this->fixtures->createPlace($other['id'], 'OP');
    $this->fixtures->assignTag($otherPlace, $otherTag);

    $response = $this->request('GET', '/api/tags/counts', null, $this->authHeader($user['id']));

    self::assertSame(200, $response->getStatusCode());

    $body = $this->jsonBody($response);
    self::assertCount(2, $body);

    $counts = array_column($body, 'assignment_count', 'name');
    self::assertSame(2, $counts['coffee']);
    self::assertSame(0, $counts['unused']);

    self::assertArrayNotHasKey('created_at', $body[0]);
  }
}
