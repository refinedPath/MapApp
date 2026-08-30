<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;

final class UuidParamMiddlewareFactory
{
  public function __construct(
    private readonly ResponseFactoryInterface $responseFactory,
  ) {
  }

  /** @param list<string> $paramNames */
  public function forParams(array $paramNames): UuidParamMiddleware
  {
    return UuidParamMiddleware::create($this->responseFactory, $paramNames);
  }
}
