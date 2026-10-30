<?php

namespace PhpCP\Tests\Fixtures;

use PhpCP\Traits\CpanelApiTrait;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * A minimal class to use the CPanel API trait outside of a command.
 */
class CpanelApiClient {

  use CpanelApiTrait;

  /**
   * Creates the client.
   */
  public function __construct(string $user, string $token, string $baseUrl, HttpClientInterface $httpClient) {
    $this->authUser = $user;
    $this->authToken = $token;
    $this->baseUrl = $baseUrl;
    $this->httpClient = $httpClient;
  }

  /**
   * Checks the credentials, the commands have the same validation.
   */
  protected function cpanelValidate(): void {
    if ($this->authUser === '' || $this->authToken === '' || $this->baseUrl === '') {
      throw new RuntimeException('Missing configuration.');
    }
  }

}
