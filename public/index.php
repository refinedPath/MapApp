<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'APP_DEBUG']);

$displayErrorDetails = filter_var($_ENV['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN);

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
  PDO::class => function (): PDO {
    $dsn = sprintf(
      'pgsql:host=%s;port=%s;dbname=%s',
      $_ENV['DB_HOST'],
      $_ENV['DB_PORT'],
      $_ENV['DB_NAME'],
    );

    return new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
      PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES    => false,
    ]);
  },
]);

$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response): Response {
  $response->getBody()->write('Hello world');
  return $response;
});

$app->get('/hello', function (Request $request, Response $response): Response {
  $response->getBody()->write('Hello from MapApp');
  return $response;
});

$app->get('/db-check', function (Request $request, Response $response) use ($container): Response {
  $pdo = $container->get(PDO::class);
  $version = $pdo->query('SELECT version()')->fetchColumn();
  $response->getBody()->write((string) $version);
  return $response;
});

$app->addRoutingMiddleware();
$app->addErrorMiddleware($displayErrorDetails, true, true);

$app->run();
