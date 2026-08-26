<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepositoryInterface;
use App\Service\TokenService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LoginController
{
  public function __construct(
    private readonly UserRepositoryInterface $users,
    private readonly TokenService $tokens,
  ) {}

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
      $response->getBody()->write((string) json_encode(['errors' => $errors]));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }

    // authenticate
    $user = $this->users->findByEmail($email);

    if ($user === null || !password_verify($password, $user->passwordHash)) {
      $response->getBody()->write((string) json_encode(['error' => 'Invalid credentials.']));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    // success - issue a token
    $token = $this->tokens->issueForUser($user->id);

    $response->getBody()->write((string) json_encode(['token' => $token]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
  }
}
