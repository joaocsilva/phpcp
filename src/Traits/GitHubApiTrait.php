<?php

namespace PhpCP\Traits;

use Github\AuthMethod;
use Github\Client;
use Github\ResultPager;
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
    $client = $this->githubClient();
    // The API returns 30 items per page by default, fetch all the pages.
    $paginator = new ResultPager($client, 100);
    $data = $paginator->fetchAll($client->repo(), 'branches', [$this->username, $this->repository]);
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
