<?php

namespace PhpCP\Traits;

use Github\AuthMethod;
use Github\Client;
use Symfony\Component\HttpClient\HttplugClient;

trait GitHubApiTrait {

  /**
   * @var string
   */
  protected string $token;

  /**
   * @var string
   */
  protected string $username;

  /**
   * @var string
   */
  protected string $repository;

  /**
   * Get branches from API.
   *
   * @return array
   *   Keyed by name and sha as value.
   */
  public function getGithubBranches(): array {
    $data = $this->githubClient()->repo()->branches($this->username, $this->repository);
    $branches = [];
    foreach ($data as $branch) {
      $branches[$branch['name']] = $branch['commit']['sha'];
    }
    return $branches;
  }

  /**
   * Creates a http client and authenticate in the API.
   */
  protected function githubClient(): Client {
    $this->githubValidate();
    $client = Client::createWithHttpClient(new HttplugClient());
    $client->authenticate($this->token, null, AuthMethod::ACCESS_TOKEN);
    return $client;
  }

}
