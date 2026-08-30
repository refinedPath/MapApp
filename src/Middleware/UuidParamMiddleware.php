<?php

declare(strict_types=1);

namespace App\Middleware;

use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Routing\RouteContext;
use Symfony\Component\Uid\Uuid;

final class UuidParamMiddleware implements MiddlewareInterface
{
  /** @param list<string> $paramNames */
  private function __construct(
    private readonly ResponseFactoryInterface $responseFactory,
    private readonly array $paramNames,
  ) {
  }

  #[Override]
  public function process(Request $request, Handler $handler): Response
  {
    $route = RouteContext::fromRequest($request)->getRoute();
    if ($route === null) {
      throw new \LogicException('UuidParamMiddleware must run after the routing middleware.');
    }

    foreach ($this->paramNames as $name) {
      $raw = $route->getArgument($name);
      if ($raw === null) {
        throw new \LogicException("UuidParamMiddleware: route has no argument '{$name}'.");
      }

      try {
        $uuid = Uuid::fromString($raw);
      } catch (\InvalidArgumentException $e) {
        return $this->notFound();
      }
      $request = $request->withAttribute($name, $uuid);
    }

    return $handler->handle($request);
  }

  /** @param list<string> $paramNames */
  public static function create(ResponseFactoryInterface $responseFactory, array $paramNames): UuidParamMiddleware
  {
    return new self($responseFactory, $paramNames);
  }

  private function notFound(): Response
  {
    $response = $this->responseFactory->createResponse(404);
    $response->getBody()->write((string) json_encode(['error' => 'Not found.']));

    return $response->withHeader('Content-Type', 'application/json');
  }
}
