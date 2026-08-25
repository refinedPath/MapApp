<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\InvalidTokenException;
use App\Service\TokenService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

final class AuthMiddleware implements MiddlewareInterface
{
  public function __construct(
    private readonly TokenService $tokens,
    private readonly ResponseFactoryInterface $responseFactory,
  ) {}

  public function process(Request $request, Handler $handler): Response
  {
    $header = $request->getHeaderLine('Authorization');

    if (!str_starts_with($header, 'Bearer ')) {
      return $this->unauthorized();
    }

    $token = substr($header, 7);

    try {
      $userId = $this->tokens->verifyToken($token);
    } catch (InvalidTokenException $e) {
      return $this->unauthorized();
    }

    $request = $request->withAttribute('userId', $userId);

    return $handler->handle($request);
  }

  private function unauthorized(): Response
  {
    $response = $this->responseFactory->createResponse(401);
    $response->getBody()->write((string) json_encode(['error' => 'Unauthorized.']));

    return $response->withHeader('Content-Type', 'application/json');
  }
}
