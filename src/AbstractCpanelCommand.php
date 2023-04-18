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
    $this->authToken = $config->get('phpcp.cpanel.token');
    $this->baseUrl = $config->get('phpcp.cpanel.url');

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
        'cp-token',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The CPanel auth token, usually a base64 encode'
      )
      ->addOption(
        'cp-base-url',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The CPanel base url, i.e: https://cpanelXXX.dnscpanel.com:XXXX/cpsessXXXXXXXXXX'
      )
      ->addUsage('--cp-token="aBcdef" --cp-base-url="https://cpanelXXX.dnscpanel.com:XXXX/cpsessXXXXXXXXXX"');
  }

  /**
   * {@inheritdoc}
   *
   * Ask for auth and base url if missing.
   */
  protected function interact(InputInterface $input, OutputInterface $output): void {
    if (empty($this->authToken)) {
      $question = new Question('Provide the CPanel auth token (base64 encode): ', '');
      $this->authToken = $this->getHelper('question')->ask($input, $output, $question);
    }
    if (empty($this->baseUrl)) {
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
