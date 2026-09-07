<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Exception\EmailAlreadyExistsException;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
  public function findByEmail(string $email): ?User;

  public function findById(Uuid $id): ?User;

  public function markEmailVerified(Uuid $userId, \DateTimeImmutable $verifiedAt): void;

  public function updatePasswordHash(Uuid $userId, string $passwordHash): void;

  /**
   * @throws EmailAlreadyExistsException
   */
  public function create(User $user): void;
}
