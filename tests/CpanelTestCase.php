<?php

namespace PhpCP\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Base class for the tests that call the CPanel API with a mocked HTTP client.
 */
abstract class CpanelTestCase extends TestCase {

  /**
   * The requests sent to the mocked client, in order.
   *
   * @var array<int, array{method: string, url: string, headers: string}>
   */
  protected array $requests = [];

  /**
   * Creates a mocked HTTP client.
   *
   * @param array<string, array{body: string, status?: int}> $responses
   *   The responses by url part, the first part found in the url is used.
   */
  protected function mockClient(array $responses): MockHttpClient {
    $this->requests = [];
    return new MockHttpClient(function (string $method, string $url, array $options) use ($responses): MockResponse {
      $this->requests[] = [
        'method' => $method,
        'url' => $url,
        'headers' => strtolower(implode("\n", $options['headers'] ?? [])),
      ];
      foreach ($responses as $urlPart => $response) {
        if (str_contains($url, $urlPart)) {
          return new MockResponse($response['body'], ['http_code' => $response['status'] ?? 200]);
        }
      }
      return new MockResponse($this->apiError('No mocked response for ' . $url), ['http_code' => 404]);
    });
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
   * Returns the urls of the requests sent to the mocked client.
   *
   * @return string[]
   *   The urls, in order.
   */
  protected function requestedUrls(): array {
    return array_column($this->requests, 'url');
  }

}
