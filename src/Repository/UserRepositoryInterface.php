<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
  public function findByEmail(string $email): ?User;

  public function findById(Uuid $id): ?User;

  public function create(User $user): void;
}
