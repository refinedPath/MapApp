<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\ConfigProvider;
use App\Http\Responder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class MeConfigController
{
  public function __construct(
    private readonly ConfigProvider $config,
  ) {
  }

  public function __invoke(Request $request, Response $response): Response
  {
    return Responder::json($response, $this->config->meConfig());
  }
}
