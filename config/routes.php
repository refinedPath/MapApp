<?php

declare(strict_types=1);

use App\Controller\LoginController;
use App\Controller\RegisterController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
  // public routes - no auth
  $app->post('/api/register', RegisterController::class);
  $app->post('/api/login', LoginController::class);

  // protected routes - require a valid token
  $app->group('/api', function (RouteCollectorProxy $group): void {
    // temporary - to test auth
    $group->get('/me', function ($request, $response) {
      $userId = $request->getAttribute('userId');
      $response->getBody()->write((string) json_encode(['userId' => (string) $userId]));
      return $response->withHeader('Content-Type', 'application/json');
    });
  })->add(AuthMiddleware::class);
};
