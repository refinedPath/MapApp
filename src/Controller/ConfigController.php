<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tag;
use App\Http\Responder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ConfigController
{
  public function __invoke(Request $request, Response $response): Response
  {
    return Responder::json($response, [
      'tag' => [
        'default_color' => Tag::DEFAULT_COLOR,
      ]
    ]);
  }
}
