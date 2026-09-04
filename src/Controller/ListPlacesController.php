<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\TagMatchMode;
use App\Http\PlaceViewSerializer;
use App\Http\Responder;
use App\ReadModel\PlaceView;
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

    $params = $request->getQueryParams();

    $tagsRaw = $params['tags'] ?? '';
    $tokens = is_string($tagsRaw)
      ? array_values(array_filter(
        array_map(static fn (string $s): string => trim($s), explode(',', $tagsRaw)),
        static fn (string $s): bool => $s !== '',
      ))
      : [];

    if ($tokens === []) {
      // No tag filter → full list; any match mode is irrelevant and ignored.
      $places = $this->places->findAllForUserWithPrimaryTag($userId);
    } else {
      $errors = [];

      $tagIds = [];
      foreach ($tokens as $token) {
        if (Uuid::isValid($token)) {
          $tagIds[] = Uuid::fromString($token);
        } else {
          $errors['tags'] = 'One or more tag ids are not valid UUIDs.';
        }
      }

      $mode = TagMatchMode::Any;
      $matchRaw = $params['match'] ?? null;
      if (is_string($matchRaw) && $matchRaw !== '') {
        $parsed = TagMatchMode::tryFrom($matchRaw);
        if ($parsed === null) {
          $errors['match'] = "Match mode must be 'any' or 'all'.";
        } else {
          $mode = $parsed;
        }
      }

      if ($errors !== []) {
        return Responder::json($response, ['errors' => $errors], 422);
      }

      $places = $this->places->findAllForUserMatchingTags($userId, $tagIds, $mode);
    }

    return Responder::json(
      $response,
      array_map(fn (PlaceView $p): array => PlaceViewSerializer::toArray($p), $places)
    );
  }
}
