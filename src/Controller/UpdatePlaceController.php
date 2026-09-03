<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Place;
use App\Http\PlaceSerializer;
use App\Http\Responder;
use App\Middleware\UuidParamMiddleware;
use App\Repository\PlaceRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

final class UpdatePlaceController
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

    $nameRaw = $data['name'] ?? null;
    $name = is_string($nameRaw) ? trim($nameRaw) : '';
    $descriptionRaw = $data['description'] ?? null;
    $description = is_string($descriptionRaw) ? trim($descriptionRaw) : null;

    $errors = [];
    if ($name === '') {
      $errors['name'] = 'Name is required.';
    } elseif (mb_strlen($name) > Place::MAX_NAME_LENGTH) {
      $errors['name'] = 'Name must be at most ' . Place::MAX_NAME_LENGTH . ' characters.';
    }

    if ($errors !== []) {
      return Responder::json($response, ['errors' => $errors], 422);
    }

    $existing = $this->places->findByIdForUser($placeId, $userId);
    if ($existing === null) {
      return Responder::json($response, ['error' => 'Not found.'], 404);
    }

    $now = new \DateTimeImmutable();

    $this->places->update(
      id: $placeId,
      userId: $userId,
      name: $name,
      description: $description,
      updatedAt: $now,
    );

    $updated = new Place(
      id: $existing->id,
      userId: $existing->userId,
      name: $name,
      description: $description,
      location: $existing->location,
      createdAt: $existing->createdAt,
      updatedAt: $now,
    );

    return Responder::json($response, PlaceSerializer::toArray($updated));
  }
}
