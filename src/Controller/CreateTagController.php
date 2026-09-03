<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tag;
use App\Exception\TagNameAlreadyExistsException;
use App\Http\Responder;
use App\Http\TagSerializer;
use App\Repository\TagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

final class CreateTagController
{
  public function __construct(
    private readonly TagRepositoryInterface $tags,
  ) {
  }

  public function __invoke(Request $request, Response $response): Response
  {
    $userId = $request->getAttribute('userId');
    if (!$userId instanceof Uuid) {
      throw new RuntimeException('Authenticated user id missing from request.');
    }
    $data = (array) $request->getParsedBody();

    $nameRaw = $data['name'] ?? null;
    $name = is_string($nameRaw) ? trim($nameRaw) : '';
    $colorRaw = $data['color'] ?? null;
    $color = is_string($colorRaw) ? trim($colorRaw) : Tag::DEFAULT_COLOR;
    $emojiRaw = $data['emoji'] ?? null;
    $emoji = is_string($emojiRaw) ? trim($emojiRaw) : '';
    $emoji = $emoji === '' ? null : $emoji;

    // validation
    $errors = [];
    if ($name === '') {
      $errors['name'] = 'Name is required.';
    } elseif (mb_strlen($name) > Tag::MAX_NAME_LENGTH) {
      $errors['name'] = 'Name must be at most ' . Tag::MAX_NAME_LENGTH . ' characters.';
    }
    if ($color === '') {
      $errors['color'] = 'Color is required.';
    } elseif (!((bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color))) {
      $errors['color'] = 'Color format is incorrect.';
    }
    if ($emoji !== null && grapheme_strlen($emoji) !== 1) {
      $errors['emoji'] = 'Emoji must be a single emoji.';
    }

    if ($errors !== []) {
      return Responder::json($response, ['errors' => $errors], 422);
    }

    $now = new \DateTimeImmutable();
    $tag = new Tag(
      id: Uuid::v7(),
      userId: $userId,
      name: $name,
      color: $color,
      emoji: $emoji,
      createdAt: $now,
      updatedAt: $now,
    );
    try {
      $this->tags->create($tag);
    } catch (TagNameAlreadyExistsException $e) {
      return Responder::json($response, ['error' => 'Tag already exists.'], 409);
    }

    return Responder::json($response, TagSerializer::toArray($tag), 201);
  }
}
