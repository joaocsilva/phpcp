<?php

namespace PhpCP;

use PhpCP\Traits\ConfigAwareTrait;
use PhpCP\Traits\CpanelApiTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

abstract class AbstractCpanelCommand extends Command {

  use CpanelApiTrait;
  use ConfigAwareTrait;

  /**
   * Validate the properties are populated.
   *
   * This is called in the interact() method, because of the option
   * --no-interaction this is also called in cpanelApiCall() method.
   */
  public function cpanelValidate(): void {
    $missing = [];
    if (empty($this->authUser) || $this->authUser === '${env.PHPCP_CPANEL_USER}') {
      $missing[] = 'phpcp.cpanel.user';
    }
    if (empty($this->authToken) || $this->authToken === '${env.PHPCP_CPANEL_TOKEN}') {
      $missing[] = 'phpcp.cpanel.token';
    }
    if (empty($this->baseUrl) || $this->baseUrl === '${env.PHPCP_CPANEL_URL}') {
      $missing[] = 'phpcp.cpanel.url';
    }
    if (!empty($missing)) {
      throw new RuntimeException(sprintf(
        'Missing configuration key(s): %s', implode(', ', $missing)
      ));
    }
  }

  /**
   * {@inheritdoc}
   *
   * Load values from environment variables or options.
   */
  protected function initialize(InputInterface $input, OutputInterface $output): void {
    $config = $this->getConfig();
    $this->authUser = $config->get('phpcp.cpanel.user') ?? '';
    $this->authToken = $config->get('phpcp.cpanel.token') ?? '';
    $this->baseUrl = $config->get('phpcp.cpanel.url') ?? '';

    if ($input->hasOption('cp-user') && $input->getOption('cp-user')) {
      $this->authUser = $input->getOption('cp-user');
    }
    if ($input->hasOption('cp-token') && $input->getOption('cp-token')) {
      $this->authToken = $input->getOption('cp-token');
    }
    if ($input->hasOption('cp-base-url') && $input->getOption('cp-base-url')) {
      $this->baseUrl = $input->getOption('cp-base-url');
    }
  }

  /**
   * {@inheritdoc}
   *
   * These options if given will override the environment variables.
   */
  protected function configure(): void {
    $this
      ->addOption(
        'cp-user',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The CPanel account username'
      )
      ->addOption(
        'cp-token',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The CPanel API token (Security > Manage API Tokens)'
      )
      ->addOption(
        'cp-base-url',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The CPanel base url, i.e: https://cpanelXXX.dnscpanel.com:2083'
      )
      ->addUsage('--cp-user="user" --cp-token="aBcdef" --cp-base-url="https://cpanelXXX.dnscpanel.com:2083"');
  }

  /**
   * {@inheritdoc}
   *
   * Ask for auth and base url if missing.
   */
  protected function interact(InputInterface $input, OutputInterface $output): void {
    if (empty($this->authUser) || $this->authUser === '${env.PHPCP_CPANEL_USER}') {
      $question = new Question('Provide the CPanel username: ', '');
      $this->authUser = $this->getHelper('question')->ask($input, $output, $question);
    }
    if (empty($this->authToken) || $this->authToken === '${env.PHPCP_CPANEL_TOKEN}') {
      $question = new Question('Provide the CPanel API token: ', '');
      $question->setHidden(TRUE);
      $this->authToken = $this->getHelper('question')->ask($input, $output, $question);
    }
    if (empty($this->baseUrl) || $this->baseUrl === '${env.PHPCP_CPANEL_URL}') {
      $question = new Question('Provide the CPanel base url: ', '');
      $this->baseUrl = $this->getHelper('question')->ask($input, $output, $question);
    }
    $this->cpanelValidate();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->cpanelValidate();
    parent::execute($input, $output);
    return Command::FAILURE;
  }

}
