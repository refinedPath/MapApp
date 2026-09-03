<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Responder;
use App\Middleware\UuidParamMiddleware;
use App\Repository\TagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

final class DeleteTagController
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
    $tagId = $request->getAttribute(UuidParamMiddleware::ATTR_PREFIX . 'tagId');
    if (!$tagId instanceof Uuid) {
      throw new RuntimeException('Tag id missing from request.');
    }

    $deleted = $this->tags->delete($tagId, $userId);
    if ($deleted === 0) {
      return Responder::json($response, ['error' => 'Not found.'], 404);
    }

    return Responder::noContent($response);
  }
}
