<?php

namespace PhpCP\Command;

use PhpCP\SecretMasker;
use PhpCP\Traits\ConfigAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigCommand extends Command {

  use ConfigAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('config')
      ->setDescription('Print configurations')
      ->addArgument(
        'key',
        InputArgument::OPTIONAL,
        'The configuration key to print'
      )
      ->addOption(
        'show-secrets',
        NULL,
        InputOption::VALUE_NONE,
        'Print the tokens and other secrets instead of masking them'
      )
      ->addUsage('phpcp.cpanel')
      ->addUsage('phpcp.cpanel.token --show-secrets');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $config = $this->getConfig();
    $key = (string) $input->getArgument('key');
    $value = $key !== '' ? $config->get($key) : $config->all();
    if (!$input->getOption('show-secrets')) {
      $value = SecretMasker::mask($value, $key);
    }
    $output->writeln(Yaml::dump($value, 10, 2));
    return Command::SUCCESS;
  }

}
