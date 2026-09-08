<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Place;
use App\Http\PlaceSerializer;
use App\Http\Responder;
use App\Repository\PlaceRepositoryInterface;
use App\Validation\CoordinateInput;
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

    [$location, $coordinateErrors] = CoordinateInput::parse($latRaw, $lngRaw);
    $errors += $coordinateErrors;

    if ($errors !== []) {
      return Responder::json($response, ['errors' => $errors], 422);
    }

    if ($location === null) {
      throw new \RuntimeException('Coordinates missing after successful validation.');
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

    return Responder::json($response, PlaceSerializer::toArray($place), 201);
  }
}
