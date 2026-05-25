<?php

namespace PhpCP\Traits;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Process\Process;

trait CpanelApiTrait {

  /**
   * @var string
   */
  protected string $authToken;

  /**
   * @var string
   */
  protected string $baseUrl;

  /**
   * Returns the CPanel repositories.
   */
  public function cpanelGetRepositories(string $repo = null): array {
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
   * Returns the CPanel repositories.
   */
  public function cpanelGetFileContent(string $dir, string $file): string {
    if (empty($dir)) {
      throw new RuntimeException('The dir parameter cannot be empty.');
    }
    if (empty($file)) {
      throw new RuntimeException('The file parameter cannot be empty.');
    }
    $url = sprintf(
      '/execute/Fileman/get_file_content?dir=%s&file=%s',
      urlencode($dir),
      urlencode($file)
    );
    $data = $this->cpanelApiCall($url);
    return $data['content'];
  }

  /**
   * Execute a CPanel API call.
   */
  public function cpanelApiCall($url) {
    $this->cpanelValidate();
    $url = rtrim($this->baseUrl . '/') . '/' . ltrim($url, '/');
    $command = ['curl', '-H', "Authorization: Basic $this->authToken", $url];
    $process = new Process($command);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new RuntimeException($process->getErrorOutput());
    }
    $response = json_decode($process->getOutput(), TRUE);
    if (empty($response)) {
      throw new RuntimeException('Fail to connect to CPanel API.');
    }
    if (!empty($response['errors'])) {
      throw new RuntimeException(implode(PHP_EOL, $response['errors']));
    }
    return $response['data'];
  }

}
