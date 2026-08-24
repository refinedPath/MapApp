<?php

declare(strict_types=1);

use App\Controller\RegisterController;
use Slim\App;

return function (App $app): void {
  $app->post('/api/register', RegisterController::class);
};
