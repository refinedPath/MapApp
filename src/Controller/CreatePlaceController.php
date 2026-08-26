<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Coordinates;
use App\Entity\Place;
use App\Exception\InvalidCoordinatesException;
use App\Http\PlaceSerializer;
use App\Repository\PlaceRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Uid\Uuid;

final class CreatePlaceController
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
    $data = (array) $request->getParsedBody();

    $nameRaw = $data['name'] ?? null;
    $name = is_string($nameRaw) ? trim($nameRaw) : '';
    $descriptionRaw = $data['description'] ?? null;
    $description = is_string($descriptionRaw) ? trim($descriptionRaw) : null;
    $latRaw = $data['latitude'] ?? null;
    $lngRaw = $data['longitude'] ?? null;

    // validation
    $errors = [];
    if ($name === '') {
      $errors['name'] = 'Name is required.';
    } elseif (mb_strlen($name) > Place::MAX_NAME_LENGTH) {
      $errors['name'] = 'Name must be at most ' . Place::MAX_NAME_LENGTH . ' characters.';
    }
    if (!is_numeric($latRaw)) {
      $errors['latitude'] = 'Latitude is required and must be numeric.';
    }
    if (!is_numeric($lngRaw)) {
      $errors['longitude'] = 'Longitude is required and must be numeric.';
    }

    if ($errors !== []) {
      $response->getBody()->write((string) json_encode(['errors' => $errors]));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }

    if (!is_numeric($latRaw) || !is_numeric($lngRaw)) {
      throw new \RuntimeException('Coordinates not numeric after validation.');
    }

    try {
      $location = new Coordinates(
        latitude: (float) $latRaw,
        longitude: (float) $lngRaw,
      );
    } catch (InvalidCoordinatesException $e) {
      $response->getBody()->write((string) json_encode([
        'errors' => ['location' => $e->getMessage()],
      ]));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }

    $now = new \DateTimeImmutable();
    $place = new Place(
      id: Uuid::v7(),
      userId: $userId,
      name: $name,
      description: $description === '' ? null : $description,
      location: $location,
      createdAt: $now,
      updatedAt: $now,
    );
    $this->places->create($place);

    $response->getBody()->write((string) json_encode(PlaceSerializer::toArray($place)));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
  }
}
