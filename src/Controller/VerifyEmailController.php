<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ExpiredVerificationTokenException;
use App\Exception\InvalidVerificationTokenException;
use App\Http\Responder;
use App\Service\EmailVerificationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class VerifyEmailController
{
  public function __construct(
    private readonly EmailVerificationService $verification,
  ) {
  }

  public function __invoke(Request $request, Response $response): Response
  {
    $data = (array) $request->getParsedBody();
    $tokenRaw = $data['token'] ?? null;
    $token = is_string($tokenRaw) ? $tokenRaw : '';

    if ($token === '') {
      return Responder::json($response, ['errors' => ['token' => 'Token is required.']], 422);
    }

    try {
      $this->verification->verify($token);
    } catch (InvalidVerificationTokenException) {
      return Responder::json($response, ['error' => 'Verification token is invalid.'], 400);
    } catch (ExpiredVerificationTokenException) {
      return Responder::json($response, ['error' => 'Verification token has expired.'], 410);
    }

    return Responder::json($response, ['message' => 'Email verified. You can now log in.'], 200);
  }
}
