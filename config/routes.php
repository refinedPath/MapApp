<?php

declare(strict_types=1);

use App\Controller\CreatePlaceController;
use App\Controller\CreateTagController;
use App\Controller\ListPlacesController;
use App\Controller\ListTagsController;
use App\Controller\LoginController;
use App\Controller\RegisterController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
  // public routes - no auth
  $app->group('/api', function (RouteCollectorProxy $group): void {
    $group->post('/register', RegisterController::class);
    $group->post('/login', LoginController::class);
  });

  // protected routes - require a valid token
  $app->group('/api', function (RouteCollectorProxy $group): void {
    $group->post('/places', CreatePlaceController::class);
    $group->get('/places', ListPlacesController::class);

    $group->post('/tags', CreateTagController::class);
    $group->get('/tags', ListTagsController::class);
  })->add(AuthMiddleware::class);
};
