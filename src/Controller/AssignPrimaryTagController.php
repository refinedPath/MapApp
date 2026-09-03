<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Responder;
use App\Middleware\UuidParamMiddleware;
use App\Repository\PlaceRepositoryInterface;
use App\Repository\PlaceTagRepositoryInterface;
use App\Repository\TagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

final class AssignPrimaryTagController
{
  public function __construct(
    private readonly PlaceRepositoryInterface $places,
    private readonly TagRepositoryInterface $tags,
    private readonly PlaceTagRepositoryInterface $placeTags,
  ) {
  }

  public function __invoke(Request $request, Response $response): Response
  {
    $userId = $request->getAttribute('userId');
    if (!$userId instanceof Uuid) {
      throw new RuntimeException('Authenticated user id missing from request.');
    }
    $placeId = $request->getAttribute(UuidParamMiddleware::ATTR_PREFIX . 'placeId');
    if (!$placeId instanceof Uuid) {
      throw new RuntimeException('Place id missing from request.');
    }
    $tagId = $request->getAttribute(UuidParamMiddleware::ATTR_PREFIX . 'tagId');
    if (!$tagId instanceof Uuid) {
      throw new RuntimeException('Tag id missing from request.');
    }

    $place = $this->places->findByIdForUser($placeId, $userId);
    if ($place === null) {
      return Responder::json($response, ['error' => 'Not found.'], 404);
    }

    $tag = $this->tags->findByIdForUser($tagId, $userId);
    if ($tag === null) {
      return Responder::json($response, ['error' => 'Not found.'], 404);
    }

    $assignedTag = $this->placeTags->isAssigned($placeId, $tagId);
    if (!$assignedTag) {
      return Responder::json($response, ['error' => 'Tag is not assigned to this place.'], 409);
    }

    $this->places->setPrimaryTag($placeId, $tagId);
    return Responder::noContent($response);
  }
}
