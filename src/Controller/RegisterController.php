<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\EmailAlreadyExistsException;
use App\Http\Responder;
use App\Repository\UserRepositoryInterface;
use App\Service\EmailVerificationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Uid\Uuid;

final class RegisterController
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
    $passwordRaw = $data['password'] ?? null;
    $password = is_string($passwordRaw) ? $passwordRaw : '';

    $errors = [];
    if ($email === '') {
      $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors['email'] = 'Email is not valid.';
    }
    if ($password === '') {
      $errors['password'] = 'Password is required.';
    } elseif (mb_strlen($password) < 8) {
      $errors['password'] = 'Password must be at least 8 characters.';
    }
    if ($errors !== []) {
      return Responder::json($response, ['errors' => $errors], 422);
    }

    // Enumeration-aware: the response is identical whether or not the email is already registered.
    if ($this->users->findByEmail($email) !== null) {
      password_hash($password, PASSWORD_DEFAULT);

      return $this->accepted($response);
    }

    $now = new \DateTimeImmutable();
    $user = new User(
      id: Uuid::v7(),
      email: $email,
      passwordHash: password_hash($password, PASSWORD_DEFAULT),
      createdAt: $now,
      updatedAt: $now,
    );

    try {
      $this->users->create($user);
    } catch (EmailAlreadyExistsException) {
      // Race between findByEmail and create — still identical response.
      return $this->accepted($response);
    }

    $this->verification->sendVerification($user);

    return $this->accepted($response);
  }

  private function accepted(Response $response): Response
  {
    // 202: accepted for processing. Outcome (email sent / account state) is not
    // synchronously revealed — and is identical across all paths.
    return Responder::json($response, [
      'message' => 'Check your email to verify your account.',
    ], 202);
  }
}
