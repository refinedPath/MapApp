<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;
use App\Exception\EmailAlreadyExistsException;

interface UserRepositoryInterface
{
  public function findByEmail(string $email): ?User;

  public function findById(Uuid $id): ?User;

  /**
   * @throws EmailAlreadyExistsException
   */
  public function create(User $user): void;
}
