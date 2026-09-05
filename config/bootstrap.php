<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\App;
use Slim\Factory\AppFactory;

return function (): App {
  $containerBuilder = new ContainerBuilder();
  $containerBuilder->addDefinitions([
    'settings' => require __DIR__ . '/settings.php',
  ]);
  $containerBuilder->addDefinitions(require __DIR__ . '/dependencies.php');
  $container = $containerBuilder->build();

  AppFactory::setContainer($container);
  $app = AppFactory::create();

  (require __DIR__ . '/routes.php')($app);

  $app->addBodyParsingMiddleware();
  $app->addRoutingMiddleware();
  $app->addErrorMiddleware(
    $container->get('settings')['displayErrorDetails'],
    true,
    true
  );

  return $app;
};
