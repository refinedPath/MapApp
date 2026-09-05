<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Service\TokenService;
use PHPUnit\Framework\TestCase;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Symfony\Component\Uid\Uuid;

abstract class FunctionalTestCase extends TestCase
{
  protected App $app;
  protected PDO $pdo;
  protected Fixtures $fixtures;

  protected function setUp(): void
  {
    $this->app = (require __DIR__ . '/../../config/bootstrap.php')();

    /** @var PDO $pdo */
    $pdo = $this->app->getContainer()->get(PDO::class);
    $this->pdo = $pdo;
    $this->fixtures = new Fixtures($this->pdo);

    $this->pdo->beginTransaction();
  }

  protected function tearDown(): void
  {
    if ($this->pdo->inTransaction()) {
      $this->pdo->rollBack();
    }
  }

  /**
   * @param array<string, mixed>|null $json
   * @param array<string, string> $headers
   */
  protected function request(string $method, string $path, ?array $json = null, array $headers = []): ResponseInterface
  {
    $request = (new ServerRequestFactory())->createServerRequest($method, $path);

    $query = parse_url($path, PHP_URL_QUERY);
    if (is_string($query)) {
      parse_str($query, $params);
      $request = $request->withQueryParams($params);
    }

    foreach ($headers as $name => $value) {
      $request = $request->withHeader($name, $value);
    }

    if ($json !== null) {
      $request = $request->withHeader('Content-Type', 'application/json');
      $request->getBody()->write((string) json_encode($json));
      $request->getBody()->rewind();
    }

    return $this->app->handle($request);
  }

  /** @return array<string, string> */
  protected function authHeader(Uuid $userId): array
  {
    /** @var TokenService $tokens */
    $tokens = $this->app->getContainer()->get(TokenService::class);

    return ['Authorization' => 'Bearer ' . $tokens->issueForUser($userId)];
  }

  /** @return array<mixed> */
  protected function jsonBody(ResponseInterface $response): array
  {
    /** @var array<mixed> $data */
    $data = json_decode((string) $response->getBody(), true);

    return $data;
  }
}
