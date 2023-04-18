<?php

namespace PhpCP\Traits;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

trait GitHubCommandTrait {

  /**
   * Validate the properties are populated.
   *
   * This is called in the interact() method. Because of the option
   * --no-interaction this is also called in githubClient() method.
   */
  public function githubValidate(): void {
    $missing = [];
    if (empty($this->token) || $this->token === '${env.PHPCP_GITHUB_TOKEN}') {
      $missing[] = 'phpcp.github.token';
    }
    if (empty($this->username) || $this->username === '${env.PHPCP_GITHUB_USER}') {
      $missing[] = 'phpcp.github.user';
    }
    if (empty($this->repository) || $this->repository === '${env.PHPCP_GITHUB_REPO}') {
      $missing[] = 'phpcp.github.repo';
    }
    if (!empty($missing)) {
      throw new RuntimeException(sprintf(
        'Missing configuration key(s): %s', implode(', ', $missing)
      ));
    }
  }

  /**
   * Adds options for override the environment variables.
   */
  public function githubAddOptions() {
    $this
      ->addOption(
        'github-token',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The GitHub API access token'
      )
      ->addOption(
        'github-user',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The GitHub username, i.e: joaocsilva'
      )
      ->addOption(
        'github-repo',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The GitHub repository, i.e: phpcp'
      );
    return $this;
  }

  /**
   * Initialize the environment variables.
   *
   * By default, the values are loaded from env vars then if options are given
   * they override.
   */
  protected function githubInitialize(InputInterface $input, OutputInterface $output): void {
    $config = $this->getConfig();
    $this->token = $config->get('phpcp.github.token');
    $this->username = $config->get('phpcp.github.user');
    $this->repository = $config->get('phpcp.github.repo');

    if ($input->hasOption('github-token') && $input->getOption('github-token')) {
      $this->token = $input->getOption('github-token');
    }
    if ($input->hasOption('github-user') && $input->getOption('github-user')) {
      $this->username = $input->getOption('github-user');
    }
    if ($input->hasOption('github-repo') && $input->getOption('github-repo')) {
      $this->repository = $input->getOption('github-repo');
    }
  }

  /**
   * Ask for token, user and repo if missing.
   */
  protected function githubInteract(InputInterface $input, OutputInterface $output): void {
    $helper = $this->getHelper('question');
    if (empty($this->token)) {
      $question = new Question('Provide the GitHub token: ', '');
      $this->token = $helper->ask($input, $output, $question);
    }
    if (empty($this->username)) {
      $question = new Question('Provide the GitHub username: ', '');
      $this->username = $helper->ask($input, $output, $question);
    }
    if (empty($this->repository)) {
      $question = new Question('Provide the GitHub repository: ', '');
      $this->repository = $helper->ask($input, $output, $question);
    }
    $this->githubValidate();
  }

}
