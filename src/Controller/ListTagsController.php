<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tag;
use App\Http\TagSerializer;
use App\Repository\TagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Uid\Uuid;

final class ListTagsController
{
  public function __construct(
    private readonly TagRepositoryInterface $tags,
  ) {
  }

  public function __invoke(Request $request, Response $response): Response
  {
    $userId = $request->getAttribute('userId');
    if (!$userId instanceof Uuid) {
      throw new \RuntimeException('Authenticated user id missing from request.');
    }
    $tags = $this->tags->findAllForUser($userId);

    $response->getBody()->write((string) json_encode(
      array_map(fn (Tag $t): array => TagSerializer::toArray($t), $tags)
    ));
    return $response->withHeader('Content-Type', 'application/json');
  }
}
