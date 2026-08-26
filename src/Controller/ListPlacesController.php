<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Place;
use App\Http\PlaceSerializer;
use App\Repository\PlaceRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Uid\Uuid;

final class ListPlacesController
{
  public function __construct(
    private readonly PlaceRepositoryInterface $places,
  ) {
  }

  public function __invoke(Request $request, Response $response): Response
  {
    $userId = $request->getAttribute('userId');
    if (!$userId instanceof Uuid) {
      throw new \RuntimeException('Authenticated user id missing from request.');
    }
    $places = $this->places->findAllForUser($userId);

    $response->getBody()->write((string) json_encode(
      array_map(fn (Place $p): array => PlaceSerializer::toArray($p), $places)
    ));
    return $response->withHeader('Content-Type', 'application/json');
  }
}
