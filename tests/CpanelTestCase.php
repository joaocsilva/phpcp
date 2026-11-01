<?php

namespace PhpCP\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Base class for the tests that call the CPanel API with a mocked HTTP client.
 */
abstract class CpanelTestCase extends TestCase {

  protected const COMMIT_A = 'a1b2c3d4e5f60718293a4b5c6d7e8f9012345678';

  protected const COMMIT_B = 'f00dbabe5f60718293a4b5c6d7e8f90123456789';

  /**
   * The requests sent to the mocked client, in order.
   *
   * @var array<int, array{method: string, url: string, headers: string}>
   */
  protected array $requests = [];

  /**
   * Creates a mocked HTTP client.
   *
   * @param array<string, array{body?: string, sequence?: string[], status?: int}> $responses
   *   The responses by url part, the first part found in the url is used. With
   *   "sequence" each request gets the next body, the last one is repeated.
   */
  protected function mockClient(array $responses): MockHttpClient {
    $this->requests = [];
    $calls = [];
    $callback = function (string $method, string $url, array $options) use ($responses, &$calls): MockResponse {
      $this->requests[] = [
        'method' => $method,
        'url' => $url,
        'headers' => strtolower(implode("\n", $options['headers'] ?? [])),
      ];
      foreach ($responses as $urlPart => $response) {
        if (str_contains($url, $urlPart)) {
          $call = $calls[$urlPart] ?? 0;
          $calls[$urlPart] = $call + 1;
          $body = isset($response['sequence'])
            ? $response['sequence'][min($call, count($response['sequence']) - 1)]
            : $response['body'];
          return new MockResponse($body, ['http_code' => $response['status'] ?? 200]);
        }
      }
      return new MockResponse($this->apiError('No mocked response for ' . $url), ['http_code' => 404]);
    };
    return new MockHttpClient($callback);
  }

  /**
   * Returns a CPanel successful response body.
   *
   * @param mixed $data
   *   The value of the response "data".
   */
  protected function apiOk($data): string {
    return json_encode(['data' => $data, 'errors' => NULL, 'status' => 1]);
  }

  /**
   * Returns a CPanel error response body.
   */
  protected function apiError(string $message): string {
    return json_encode(['data' => NULL, 'errors' => [$message], 'status' => 0]);
  }

  /**
   * Returns a CPanel repository, as in the API response.
   */
  protected function repository(array $values = []): array {
    return $values + [
      'name' => 'my-site',
      'repository_root' => '/home/user/repositories/my-site',
      'branch' => 'main',
      'deployable' => 1,
    ];
  }

  /**
   * Returns the CPanel response of the repositories, with a HEAD commit.
   *
   * @param string $head
   *   The HEAD commit.
   * @param string|null $deployed
   *   The last deployed commit, NULL if it was never deployed.
   * @param array $values
   *   Other values of the repository.
   */
  protected function repositories(string $head, ?string $deployed = NULL, array $values = []): string {
    $repository = $values + ['last_update' => ['identifier' => $head]];
    if ($deployed !== NULL) {
      $repository['last_deployment'] = ['repository_state' => ['identifier' => $deployed]];
    }
    return $this->apiOk([$this->repository($repository)]);
  }

  /**
   * Returns the urls of the requests sent to the mocked client.
   *
   * @return string[]
   *   The urls, in order.
   */
  protected function requestedUrls(): array {
    return array_column($this->requests, 'url');
  }

}
