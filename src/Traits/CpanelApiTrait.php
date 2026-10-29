<?php

namespace PhpCP\Traits;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

trait CpanelApiTrait {

  /**
   * @var string
   */
  protected string $authUser;

  /**
   * @var string
   */
  protected string $authToken;

  /**
   * @var string
   */
  protected string $baseUrl;

  /**
   * @var \Symfony\Contracts\HttpClient\HttpClientInterface|null
   */
  protected ?HttpClientInterface $httpClient = NULL;

  /**
   * Returns the CPanel repositories.
   */
  public function cpanelGetRepositories(string $repo = NULL): array {
    $repositories = $this->cpanelApiCall('/execute/VersionControl/retrieve');
    if (!empty($repo)) {
      foreach ($repositories as $repository) {
        if ($repo && $repository['name'] === $repo) {
          return [$repository];
        }
      }
      return [];
    }
    return $repositories;
  }

  /**
   * Returns the content of a CPanel file.
   */
  public function cpanelGetFileContent(string $dir, string $file): string {
    if (empty($dir)) {
      throw new RuntimeException('The dir parameter cannot be empty.');
    }
    if (empty($file)) {
      throw new RuntimeException('The file parameter cannot be empty.');
    }
    $data = $this->cpanelApiCall('/execute/Fileman/get_file_content', [
      'dir' => $dir,
      'file' => $file,
    ]);
    return $data['content'];
  }

  /**
   * Set the HTTP client, useful to replace it in tests.
   */
  public function setHttpClient(HttpClientInterface $httpClient): void {
    $this->httpClient = $httpClient;
  }

  /**
   * Execute a CPanel API call using an API token.
   *
   * @param string $endpoint
   *   The API endpoint, i.e: /execute/VersionControl/retrieve.
   * @param array $query
   *   The query parameters, these are url encoded.
   *
   * @return mixed
   *   The "data" value from the API response.
   *
   * @see https://api.docs.cpanel.net/guides/guide-to-api-authentication/guide-to-api-authentication-api-tokens/
   */
  public function cpanelApiCall(string $endpoint, array $query = []) {
    $this->cpanelValidate();
    $url = $this->cpanelBaseUrl() . '/' . ltrim($endpoint, '/');
    $client = $this->httpClient ??= HttpClient::create(['timeout' => 30]);

    try {
      $response = $client->request('GET', $url, [
        'headers' => [
          'Authorization' => sprintf('cpanel %s:%s', $this->authUser, $this->authToken),
          'Accept' => 'application/json',
        ],
        'query' => $query,
      ]);
      $status = $response->getStatusCode();
      $body = $response->getContent(FALSE);
    }
    catch (ExceptionInterface $e) {
      throw new RuntimeException('Fail to connect to CPanel API: ' . $e->getMessage(), 0, $e);
    }

    if (in_array($status, [401, 403], TRUE)) {
      throw new RuntimeException(sprintf(
        'CPanel rejected the credentials (HTTP %d). Check phpcp.cpanel.user and phpcp.cpanel.token, the token must'
         . ' be an API token (Security > Manage API Tokens), not the account password.',
        $status
      ));
    }

    $json = json_decode($body, TRUE);
    if (!is_array($json)) {
      // CPanel answers with the HTML login page when it does not authorize
      // the request, i.e: HTTP Basic authentication is disabled.
      throw new RuntimeException(sprintf(
        'Unexpected response from CPanel API (HTTP %d), expected JSON. Check the url and the credentials.',
        $status
      ));
    }
    if (!empty($json['errors'])) {
      throw new RuntimeException(implode(PHP_EOL, (array) $json['errors']));
    }
    if (isset($json['status']) && (int) $json['status'] === 0) {
      throw new RuntimeException('CPanel API call failed without an error message.');
    }
    return $json['data'] ?? [];
  }

  /**
   * Returns the CPanel API base url, without any session segment.
   *
   * API token authentication does not use the temporary /cpsessXXXXXXXXXX
   * segment, it is removed if present in the configured url.
   */
  protected function cpanelBaseUrl(): string {
    $url = preg_replace('#/cpsess[^/]*#', '', $this->baseUrl);
    return rtrim($url, '/');
  }

}
