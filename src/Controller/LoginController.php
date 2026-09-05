<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Responder;
use App\Repository\UserRepositoryInterface;
use App\Service\TokenService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LoginController
{
  private const DUMMY_HASH = '$2y$10$B8smntkzLe9LfTu7NBffGej.Pw.BWPzcebqo9t8kK32WfMwj2KD0G';

  public function __construct(
    private readonly UserRepositoryInterface $users,
    private readonly TokenService $tokens,
  ) {
  }

  public function __invoke(Request $request, Response $response): Response
  {
    $data = (array) $request->getParsedBody();

    $emailRaw = $data['email'] ?? null;
    $email = is_string($emailRaw) ? trim($emailRaw) : '';
    $passwordRaw = $data['password'] ?? null;
    $password = is_string($passwordRaw) ? $passwordRaw : '';

    // validation
    $errors = [];
    if ($email === '') {
      $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors['email'] = 'Email is not valid.';
    }
    if ($password === '') {
      $errors['password'] = 'Password is required.';
    }

    if ($errors !== []) {
      return Responder::json($response, ['errors' => $errors], 422);
    }

    // authenticate — verify against a dummy hash when the user is unknown so the
    // unknown-email and wrong-password paths do the same bcrypt work (no timing
    // side channel that would let an attacker enumerate registered emails).
    $user = $this->users->findByEmail($email);
    $hash = $user->passwordHash ?? self::DUMMY_HASH;

    if (!password_verify($password, $hash) || $user === null) {
      return Responder::json($response, ['error' => 'Invalid credentials.'], 401);
    }

    // success - issue a token
    $token = $this->tokens->issueForUser($user->id);

    return Responder::json($response, ['token' => $token], 200);
  }
}
