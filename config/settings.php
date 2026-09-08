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
    'ttl' => (int) ($_ENV['JWT_TTL_SECONDS'] ?? 60 * 15),  // default 15m
    'issuer' => 'mapapp',
    'audience' => 'mapapp',
  ],
  'mail' => [
    'transport' => $_ENV['MAIL_TRANSPORT'] ?? 'log',
    'verification_ttl_hours' => (int) ($_ENV['VERIFICATION_TTL_HOURS'] ?? 1),
  ],
  'app' => [
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
  ],
  'password' => [
    'min_length' => (int) ($_ENV['PASSWORD_MIN_LENGTH'] ?? 12),
    'require_uppercase' => filter_var($_ENV['PASSWORD_REQUIRE_UPPERCASE'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'require_lowercase' => filter_var($_ENV['PASSWORD_REQUIRE_LOWERCASE'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'require_number' => filter_var($_ENV['PASSWORD_REQUIRE_NUMBER'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'require_symbol' => filter_var($_ENV['PASSWORD_REQUIRE_SYMBOL'] ?? true, FILTER_VALIDATE_BOOLEAN),
  ],
];
