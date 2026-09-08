<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Place;
use App\Http\PlaceSerializer;
use App\Http\Responder;
use App\Middleware\UuidParamMiddleware;
use App\Repository\PlaceRepositoryInterface;
use App\Validation\CoordinateInput;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

final class UpdatePlaceLocationController
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

    $data = (array) $request->getParsedBody();

    [$location, $errors] = CoordinateInput::parse($data['latitude'] ?? null, $data['longitude'] ?? null);
    if ($errors !== []) {
      return Responder::json($response, ['errors' => $errors], 422);
    }
    if ($location === null) {
      throw new RuntimeException('Coordinates missing after successful validation.');
    }

    $existing = $this->places->findByIdForUser($placeId, $userId);
    if ($existing === null) {
      return Responder::json($response, ['error' => 'Not found.'], 404);
    }

    $now = new \DateTimeImmutable();
    $this->places->updateLocation($placeId, $userId, $location, $now);

    $updated = new Place(
      id: $existing->id,
      userId: $existing->userId,
      name: $existing->name,
      description: $existing->description,
      location: $location,
      createdAt: $existing->createdAt,
      updatedAt: $now,
    );

    return Responder::json($response, PlaceSerializer::toArray($updated));
  }
}
