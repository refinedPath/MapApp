<?php

declare(strict_types=1);

use App\Repository\UserRepository;
use App\Repository\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

return [
  PDO::class => function (ContainerInterface $c): PDO {
    $db = $c->get('settings')['db'];
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $db['host'], $db['port'], $db['name']);

    return new PDO($dsn, $db['user'], $db['pass'], [
      PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES    => false,
    ]);
  },

  UserRepositoryInterface::class => function (ContainerInterface $c): UserRepository {
    return new UserRepository($c->get(PDO::class));
  },
];
