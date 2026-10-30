<?php

namespace PhpCP\Tests\Traits;

use PhpCP\Tests\CpanelTestCase;
use PhpCP\Tests\Fixtures\CpanelApiClient;
use Symfony\Component\Console\Exception\RuntimeException;

class CpanelApiTraitTest extends CpanelTestCase {

  /**
   * Tests that the request uses the API token header.
   */
  public function testSendsApiTokenHeader(): void {
    $client = $this->client(['VersionControl/retrieve' => ['body' => $this->apiOk([])]]);
    $client->cpanelApiCall('/execute/VersionControl/retrieve');
    $this->assertCount(1, $this->requests);
    $this->assertSame('GET', $this->requests[0]['method']);
    $this->assertStringContainsString('authorization: cpanel me:token123', $this->requests[0]['headers']);
    $this->assertStringNotContainsString('basic', $this->requests[0]['headers']);
  }

  /**
   * Tests that the session segment of the base url is not requested.
   */
  public function testRemovesSessionFromBaseUrl(): void {
    $client = $this->client(
      ['VersionControl/retrieve' => ['body' => $this->apiOk([])]],
      'https://cpanel.example.com:2083/cpsess1234567890/'
    );
    $client->cpanelApiCall('/execute/VersionControl/retrieve');
    $this->assertSame(
      ['https://cpanel.example.com:2083/execute/VersionControl/retrieve'],
      $this->requestedUrls()
    );
  }

  /**
   * Tests that the query parameters are url encoded.
   */
  public function testEncodesQueryParameters(): void {
    $client = $this->client(['Fileman' => ['body' => $this->apiOk([])]]);
    $client->cpanelApiCall('/execute/Fileman/get_file_content', ['dir' => '/home/my user', 'file' => 'a&b.txt']);
    $this->assertStringContainsString('dir=/home/my%20user', $this->requestedUrls()[0]);
    $this->assertStringContainsString('file=a%26b.txt', $this->requestedUrls()[0]);
  }

  /**
   * Tests that the response data is returned.
   */
  public function testReturnsData(): void {
    $client = $this->client(['retrieve' => ['body' => $this->apiOk([['name' => 'a']])]]);
    $this->assertSame([['name' => 'a']], $client->cpanelApiCall('/execute/VersionControl/retrieve'));
  }

  /**
   * Tests that rejected credentials have a helpful message.
   */
  public function testRejectedCredentials(): void {
    $client = $this->client(['retrieve' => ['body' => 'Unauthorized', 'status' => 401]]);
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('CPanel rejected the credentials (HTTP 401)');
    $client->cpanelApiCall('/execute/VersionControl/retrieve');
  }

  /**
   * Tests that the HTML login page is not handled as a valid response.
   */
  public function testHtmlResponseIsAnError(): void {
    $client = $this->client(['retrieve' => ['body' => '<html><body>Login</body></html>']]);
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('expected JSON');
    $client->cpanelApiCall('/execute/VersionControl/retrieve');
  }

  /**
   * Tests that the API errors are the exception message.
   */
  public function testApiErrors(): void {
    $client = $this->client(['retrieve' => ['body' => $this->apiError('Permission denied')]]);
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Permission denied');
    $client->cpanelApiCall('/execute/VersionControl/retrieve');
  }

  /**
   * Tests that a failed status without errors is an error.
   */
  public function testFailedStatusWithoutErrors(): void {
    $client = $this->client(['retrieve' => ['body' => '{"data":null,"errors":null,"status":0}']]);
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('failed without an error message');
    $client->cpanelApiCall('/execute/VersionControl/retrieve');
  }

  /**
   * Tests that the repositories can be filtered by name.
   */
  public function testGetRepositoriesByName(): void {
    $repositories = [$this->repository(), $this->repository(['name' => 'other'])];
    $client = $this->client(['retrieve' => ['body' => $this->apiOk($repositories)]]);
    $this->assertCount(2, $client->cpanelGetRepositories());
    $found = $client->cpanelGetRepositories('other');
    $this->assertCount(1, $found);
    $this->assertSame('other', $found[0]['name']);
    $this->assertSame([], $client->cpanelGetRepositories('missing'));
  }

  /**
   * Tests that the file content is returned.
   */
  public function testGetFileContent(): void {
    $client = $this->client(['get_file_content' => ['body' => $this->apiOk(['content' => "{}\n"])]]);
    $this->assertSame("{}\n", $client->cpanelGetFileContent('/home/user', 'composer.json'));
  }

  /**
   * Tests that a response without the content is an error.
   */
  public function testGetFileContentWithoutContent(): void {
    $client = $this->client(['get_file_content' => ['body' => $this->apiOk([])]]);
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('returned no content');
    $client->cpanelGetFileContent('/home/user', 'composer.json');
  }

  /**
   * Creates a client with a mocked HTTP client.
   *
   * @param array<string, array{body: string, status?: int}> $responses
   *   The mocked responses.
   */
  private function client(array $responses, string $baseUrl = 'https://cpanel.example.com:2083'): CpanelApiClient {
    return new CpanelApiClient('me', 'TOKEN123', $baseUrl, $this->mockClient($responses));
  }

}
