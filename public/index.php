<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
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

$app->run();
