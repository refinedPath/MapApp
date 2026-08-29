<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\EmailAlreadyExistsException;
use App\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Uid\Uuid;

final class RegisterController
{
  public function __construct(
    private readonly UserRepositoryInterface $users,
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
    } elseif (mb_strlen($password) < 8) {
      $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($errors !== []) {
      $response->getBody()->write((string) json_encode(['errors' => $errors]));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }

    // duplicate check
    if ($this->users->findByEmail($email) !== null) {
      $response->getBody()->write(((string) json_encode(['error' => 'Email already registered.'])));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
    }

    // create
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
    } catch (EmailAlreadyExistsException $e) {
      $response->getBody()->write(((string) json_encode(['error' => 'Email already registered.'])));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
    }

    $response->getBody()->write((string) json_encode([
      'id' => $user->id->toRfc4122(),
      'email' => $user->email,
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
  }
}
