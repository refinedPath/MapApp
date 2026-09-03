<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ResponseInterface as Response;

final class Responder
{
  /**
   * JSON body + Content-Type with optional status
   *
   * @param array<mixed> $data
   */
  public static function json(Response $response, array $data, int $status = 200): Response
  {
    $response->getBody()->write((string) json_encode($data));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus($status);
  }

  /**
   * 204 No Content (no body, no Content-Type)
   */
  public static function noContent(Response $response): Response
  {
    return $response->withStatus(204)->withoutHeader('Content-Type');
  }
}
