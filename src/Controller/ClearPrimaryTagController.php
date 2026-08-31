<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\UuidParamMiddleware;
use App\Repository\PlaceRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

final class ClearPrimaryTagController
{
  public function __construct(
    private readonly PlaceRepositoryInterface $places,
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
      $response->getBody()->write((string) json_encode(['error' => 'Not found.']));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $this->places->clearPrimaryTag($placeId);
    return $response->withStatus(204)->withoutHeader('Content-Type');
  }
}
