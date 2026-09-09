<?php

declare(strict_types=1);

use App\Controller\RegisterController;
use App\Http\ConfigProvider;
use App\Mail\InMemoryMailer;
use App\Mail\LogMailer;
use App\Mail\MailerInterface;
use App\Middleware\UuidParamMiddlewareFactory;
use App\Repository\EmailVerificationTokenRepository;
use App\Repository\EmailVerificationTokenRepositoryInterface;
use App\Repository\PlaceRepository;
use App\Repository\PlaceRepositoryInterface;
use App\Repository\PlaceTagRepository;
use App\Repository\PlaceTagRepositoryInterface;
use App\Repository\TagRepository;
use App\Repository\TagRepositoryInterface;
use App\Repository\UserRepository;
use App\Repository\UserRepositoryInterface;
use App\Service\EmailVerificationService;
use App\Service\TokenService;
use App\Validation\PasswordPolicy;
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

  PasswordPolicy::class => function (ContainerInterface $c): PasswordPolicy {
    $p = $c->get('settings')['password'];

    return new PasswordPolicy(
      minLength: $p['min_length'],
      requireUppercase: $p['require_uppercase'],
      requireLowercase: $p['require_lowercase'],
      requireNumber: $p['require_number'],
      requireSymbol: $p['require_symbol'],
    );
  },

  TokenService::class => function (ContainerInterface $c): TokenService {
    return new TokenService(
      $c->get('settings')['jwt']['secret'],
      $c->get('settings')['jwt']['ttl'],
      $c->get('settings')['jwt']['issuer'],
      $c->get('settings')['jwt']['audience'],
    );
  },

  MailerInterface::class => function (ContainerInterface $c): MailerInterface {
    return match ($c->get('settings')['mail']['transport']) {
      'memory' => new InMemoryMailer(),
      'log' => new LogMailer(),
      default => throw new \RuntimeException('Unknown mail transport: ' . $c->get('settings')['mail']['transport']),
    };
  },

  EmailVerificationTokenRepositoryInterface::class => function (ContainerInterface $c): EmailVerificationTokenRepository {
    return new EmailVerificationTokenRepository($c->get(PDO::class));
  },

  EmailVerificationService::class => function (ContainerInterface $c): EmailVerificationService {
    return new EmailVerificationService(
      $c->get(EmailVerificationTokenRepositoryInterface::class),
      $c->get(UserRepositoryInterface::class),
      $c->get(MailerInterface::class),
      $c->get('settings')['app']['url'],
      $c->get('settings')['mail']['verification_ttl_hours'],
    );
  },

  RegisterController::class => function (ContainerInterface $c): RegisterController {
    return new RegisterController(
      $c->get(UserRepositoryInterface::class),
      $c->get(EmailVerificationService::class),
      $c->get(PasswordPolicy::class),
      $c->get('settings')['registration']['auto_verify_new_accounts'],
    );
  },

  ConfigProvider::class => function (ContainerInterface $c): ConfigProvider {
    return new ConfigProvider(
      $c->get(PasswordPolicy::class),
      $c->get('settings')['registration']['auto_verify_new_accounts'],
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
