<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Responder;
use App\Repository\UserRepositoryInterface;
use App\Service\EmailVerificationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ResendVerificationController
{
  public function __construct(
    private readonly UserRepositoryInterface $users,
    private readonly EmailVerificationService $verification,
  ) {
  }

  public function __invoke(Request $request, Response $response): Response
  {
    $data = (array) $request->getParsedBody();
    $emailRaw = $data['email'] ?? null;
    $email = is_string($emailRaw) ? trim($emailRaw) : '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return Responder::json($response, ['errors' => ['email' => 'A valid email is required.']], 422);
    }

    // Enumeration-aware. Identical response regardless of whether the account
    // exists or is already verified. Only actually (re)send for a real,
    // still-unverified user. The service's cooldown bounds outbound volume.
    $user = $this->users->findByEmail($email);
    if ($user !== null && $user->emailVerifiedAt === null) {
      $this->verification->sendVerification($user);
    }

    return Responder::json($response, [
      'message' => 'If that account exists and is unverified, a new verification email has been sent.',
    ], 202);
  }
}
