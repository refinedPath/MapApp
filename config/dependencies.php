<?php

declare(strict_types=1);

use App\Middleware\UuidParamMiddlewareFactory;
use App\Repository\PlaceRepository;
use App\Repository\PlaceRepositoryInterface;
use App\Repository\PlaceTagRepository;
use App\Repository\PlaceTagRepositoryInterface;
use App\Repository\TagRepository;
use App\Repository\TagRepositoryInterface;
use App\Repository\UserRepository;
use App\Repository\UserRepositoryInterface;
use App\Service\TokenService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

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

  TokenService::class => function (ContainerInterface $c): TokenService {
    return new TokenService(
      $c->get('settings')['jwt']['secret'],
      $c->get('settings')['jwt']['ttl'],
    );
  },

  ResponseFactoryInterface::class => fn (): ResponseFactory => new ResponseFactory(),

  UuidParamMiddlewareFactory::class => fn (ContainerInterface $c): UuidParamMiddlewareFactory => new UuidParamMiddlewareFactory($c->get(ResponseFactoryInterface::class)),

  PlaceRepositoryInterface::class => function (ContainerInterface $c): PlaceRepository {
    return new PlaceRepository($c->get(PDO::class));
  },

  TagRepositoryInterface::class => function (ContainerInterface $c): TagRepository {
    return new TagRepository($c->get(PDO::class));
  },

  PlaceTagRepositoryInterface::class => function (ContainerInterface $c): PlaceTagRepository {
    return new PlaceTagRepository($c->get(PDO::class));
  }
];
