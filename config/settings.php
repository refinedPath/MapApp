<?php

declare(strict_types=1);

return [
  'displayErrorDetails' => filter_var($_ENV['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN),
  'db' => [
    'host' => $_ENV['DB_HOST'],
    'port' => $_ENV['DB_PORT'],
    'name' => $_ENV['DB_NAME'],
    'user' => $_ENV['DB_USER'],
    'pass' => $_ENV['DB_PASS'],
  ],
  'jwt' => [
    'secret' => $_ENV['JWT_SECRET'],
    'ttl' => 60 * 60 * 8,  // 8 hours
    'issuer' => 'mapapp',
    'audience' => 'mapapp',
  ],
];
