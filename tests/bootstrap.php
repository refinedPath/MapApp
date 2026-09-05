<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.test');
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'APP_DEBUG', 'JWT_SECRET']);

if (!str_ends_with((string) $_ENV['DB_NAME'], '_test')) {
  fwrite(STDERR, "REFUSING TO RUN TESTS: DB_NAME '{$_ENV['DB_NAME']}' is not a test database (must end in _test).\n");
  exit(1);
}
