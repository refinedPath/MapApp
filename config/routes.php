<?php

declare(strict_types=1);

use App\Controller\AssignPrimaryTagController;
use App\Controller\AssignTagController;
use App\Controller\ClearPrimaryTagController;
use App\Controller\CreatePlaceController;
use App\Controller\CreateTagController;
use App\Controller\DeletePlaceController;
use App\Controller\DeleteTagController;
use App\Controller\ListPlacesController;
use App\Controller\ListPlaceTagsController;
use App\Controller\ListTagsController;
use App\Controller\LoginController;
use App\Controller\RegisterController;
use App\Controller\ShowPlaceController;
use App\Controller\UnassignTagController;
use App\Controller\UpdatePlaceController;
use App\Controller\UpdateTagController;
use App\Middleware\AuthMiddleware;
use App\Middleware\UuidParamMiddlewareFactory;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
  $container = $app->getContainer();
  if ($container === null) {
    throw new \RuntimeException('Container not set on app.');
  }

  // public routes - no auth
  $app->group('/api', function (RouteCollectorProxy $group): void {
    $group->post('/register', RegisterController::class);
    $group->post('/login', LoginController::class);
  });

  // protected routes - require a valid token
  $app->group('/api', function (RouteCollectorProxy $group) use ($container): void {
    $group->post('/places', CreatePlaceController::class);
    $group->get('/places', ListPlacesController::class);

    $group->post('/tags', CreateTagController::class);
    $group->get('/tags', ListTagsController::class);

    $group->group('/places/{placeId}', function (RouteCollectorProxy $g): void {
      $g->get('', ShowPlaceController::class);
      $g->put('', UpdatePlaceController::class);
      $g->delete('', DeletePlaceController::class);
    })->add($container->get(UuidParamMiddlewareFactory::class)->forParams(['placeId']));

    $group->group('/tags/{tagId}', function (RouteCollectorProxy $g): void {
      $g->put('', UpdateTagController::class);
      $g->delete('', DeleteTagController::class);
    })->add($container->get(UuidParamMiddlewareFactory::class)->forParams(['tagId']));

    $group->group('/places/{placeId}/tags', function (RouteCollectorProxy $g): void {
      $g->put('/{tagId}', AssignTagController::class);
      $g->delete('/{tagId}', UnassignTagController::class);
    })->add($container->get(UuidParamMiddlewareFactory::class)->forParams(['placeId', 'tagId']));

    $group->group('/places/{placeId}/tags', function (RouteCollectorProxy $g): void {
      $g->get('', ListPlaceTagsController::class);
    })->add($container->get(UuidParamMiddlewareFactory::class)->forParams(['placeId']));

    $group->group('/places/{placeId}/primary-tag', function (RouteCollectorProxy $g): void {
      $g->put('/{tagId}', AssignPrimaryTagController::class);
    })->add($container->get(UuidParamMiddlewareFactory::class)->forParams(['placeId', 'tagId']));

    $group->group('/places/{placeId}/primary-tag', function (RouteCollectorProxy $g): void {
      $g->delete('', ClearPrimaryTagController::class);
    })->add($container->get(UuidParamMiddlewareFactory::class)->forParams(['placeId']));
  })->add(AuthMiddleware::class);
};
