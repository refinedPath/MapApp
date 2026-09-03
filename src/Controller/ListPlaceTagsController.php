<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tag;
use App\Http\Responder;
use App\Http\TagSerializer;
use App\Middleware\UuidParamMiddleware;
use App\Repository\PlaceRepositoryInterface;
use App\Repository\PlaceTagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

final class ListPlaceTagsController
{
  public function __construct(
    private readonly PlaceRepositoryInterface $places,
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

    $place = $this->places->findByIdForUser($placeId, $userId);
    if ($place === null) {
      return Responder::json($response, ['error' => 'Not found.'], 404);
    }

    $tags = $this->placeTags->findTagsForPlace($placeId);

    return Responder::json(
      $response,
      array_map(fn (Tag $t): array => TagSerializer::toArray($t), $tags)
    );
  }
}
